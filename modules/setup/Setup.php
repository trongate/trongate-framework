<?php
/**
 * First-run setup wizard.
 *
 * This class is deliberately not a Trongate module class and does not extend
 * Trongate. It is invoked from Core::attempt_invoke_setup_wizard(), which runs
 * before routing exists. Consequently there are no modules, no validation
 * library, no CSRF tokens and no Trongate MX available here - everything is
 * plain PHP, drawn as a single page, with every form posting back to /setup.
 *
 * Until config/config.php holds a real BASE_URL the application counts as not
 * installed as far as Core is concerned, so every stage but the last is served
 * by the first-run check, at whatever address the developer opens. The URL is
 * the last thing the wizard asks for, so the last post - the one that arrives
 * with that file already saved - is the only one that is routed: index() is the
 * entry point, and the form posts to /setup.
 *
 * The step-by-step path writes nothing to the filesystem: where a file has to
 * change (config/config.php, config/database.php) the wizard prints the code to
 * paste and then verifies the result with a live check on the next request.
 *
 * The application's URL is the last thing that is written, by either path,
 * because "BASE_URL is still '****'" is what Core reads as "not installed yet".
 * Written earlier, a failure in a later stage would switch the first-run check
 * off and leave an application that cannot be finished or reached.
 *
 * The one exception is the automatic installation, offered on the first stage
 * when the environment permits it. It requires that PHP can write to config/
 * and delete this module, that the request came from the local machine, and
 * that the database server is reachable and does not already hold an
 * installation. When all of that holds the wizard saves the database settings,
 * creates the database and runs the framework's SQL, asks for the administrator
 * account, and only then writes the URL and removes this module.
 */
class Setup {

    /**
     * The stages of the wizard.
     *
     * @var array<int, string>
     */
    private array $steps = [
        1 => 'Welcome',
        2 => 'Database',
        3 => 'Admin',
        4 => 'Base URL',
        5 => 'Finalize'
    ];

    /**
     * The application's URL, derived from the request.
     */
    private string $base_url = '';

    /**
     * The stage currently being drawn.
     */
    private int $step = 1;

    /**
     * Wizard state, persisted in the session between requests.
     *
     * @var array<string, mixed>
     */
    private array $state = [];

    /**
     * Field level error messages, keyed by field name.
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * A message that does not belong to a single field.
     */
    private string $notice = '';

    /**
     * Set when a live database connection succeeded on THIS request.
     */
    private bool $database_verified = false;

    /**
     * Set when an administrator row was confirmed on THIS request.
     */
    private bool $admin_verified = false;

    /**
     * Set when this request is the one that arrives at the final stage.
     */
    private bool $celebrate = false;

    /**
     * Set when this run of the wizard is the automatic installation.
     */
    private bool $fast_mode = false;

    /**
     * The result of the environment probe, cached for the request.
     *
     * @var array<string, mixed>|null
     */
    private ?array $probe = null;

    /**
     * Set when this module is to be deleted once the page has been drawn.
     */
    private bool $module_deletion_pending = false;

    /**
     * Set when this module was expected to be deleted on this request.
     */
    private bool $module_deletion_expected = false;

    /**
     * The wizard is a development tool only.
     *
     * Core calls this class from a single comparison ahead of routing -
     * `if (BASE_URL === '****')` - which carries no ENV test, so that the test
     * is paid once per wizard request instead of once per request for the
     * lifetime of the application. The check is made here instead, and nothing
     * is drawn when it fails: a live server is given the same 404 it would get
     * if this module were absent.
     */
    public function __construct() {
        if ($this->is_development() === false) {
            http_response_code(404);
            echo 'Not found';
            die();
        }
    }

    /**
     * Is this a development installation?
     *
     * @return bool
     */
    private function is_development(): bool {
        if (defined('ENV') === false) {
            return false;
        }

        return strtolower((string) ENV) === 'dev';
    }

    /**
     * Run the wizard: work out the request, then draw the current stage.
     *
     * @return void
     */
    public function run(): void {
        $this->base_url = $this->get_assumed_base_url();
        $this->load_state();

        if ($this->is_status_request() === true) {
            $this->draw_status();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_post();
        }

        $this->apply_state_guards();
        $this->celebrate = $this->should_celebrate();

        // Make sure a token exists before the state is written, so that the
        // token in the forms matches the token in the session.
        $this->get_token();

        if ($this->module_deletion_pending === true) {
            $this->module_deletion_expected = $this->can_delete_module_folder();
        }

        $this->state['step'] = $this->step;
        $_SESSION['setup_wizard'][$this->get_state_key()] = $this->state;

        // The page has to be built before this module is deleted, because the
        // view files and the stylesheet are read from the folder being removed.
        ob_start();
        require __DIR__ . '/views/setup_template.php';
        $html = (string) ob_get_clean();

        if ($this->module_deletion_pending === true) {
            $this->module_deletion_pending = false;

            if ($this->delete_module_folder() === false) {
                $message = 'The <code>setup</code> module could not be removed automatically. '
                    . 'Please delete <code>' . out(APPPATH . 'modules/setup') . '</code> by hand, '
                    . 'otherwise this wizard will keep appearing in place of your application.';
                $html = $this->append_alert($html, $message);
            }

            if ($this->remove_setup_gate() === false) {
                $message = 'The first-run check in <code>engine/Core.php</code> could not be removed '
                    . 'automatically. It is a single comparison of <code>BASE_URL</code>, which is false '
                    . 'from now on, so it can safely be left where it is.';
                $html = $this->append_alert($html, $message);
            }
        }

        echo $html;
    }

    /**
     * Is this the page's silent permission check?
     *
     * The welcome stage polls this while it waits for a terminal command to be
     * run, so that the developer is never asked to click a button to tell the
     * page something the page can see for itself.
     *
     * @return bool
     */
    private function is_status_request(): bool {
        return ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['setup_status']));
    }

    /**
     * Answer the permission check and end the request.
     *
     * Only the filesystem is examined here and no database connection is made:
     * the question being polled is whether PHP can now write the configuration
     * files and remove this module, which is exactly what the terminal command
     * changes.
     *
     * @return void
     */
    private function draw_status(): void {
        $permissions = (
            is_writable(APPPATH . 'config') === true
            && $this->can_delete_module_folder() === true
        );

        header('Content-Type: application/json');
        echo json_encode(['permissions' => $permissions]);
        die();
    }

    /**
     * Keep the stage honest: a stage cannot be drawn unless the stage that
     * feeds it has actually been completed.
     *
     * @return void
     */
    private function apply_state_guards(): void {
        if ($this->step >= 3 && empty($this->state['database_file_verified'])) {
            $this->step = 2;
        }

        if ($this->step >= 4 && empty($this->state['admin_created'])) {
            $this->step = 3;
        }

        if ($this->step >= 5 && empty($this->state['base_url_confirmed'])) {
            $this->step = 4;
        }
    }

    /**
     * The application's base URL, as it should be assumed to be.
     *
     * config/config.php is authoritative once it holds a real value; while it
     * holds the placeholder the URL is derived from the request.
     *
     * @return string The base URL, with a trailing slash.
     */
    private function get_assumed_base_url(): string {
        // Once config/config.php has a real BASE_URL there is nothing to assume.
        if (BASE_URL !== '****') {
            return BASE_URL;
        }

        return $this->derive_base_url();
    }

    /**
     * Work out the application's URL from the request that is being served.
     *
     * SCRIPT_NAME is the URL of the script that is actually executing, after
     * rewriting and after any server Alias, which is exactly the question being
     * asked here. The application's script normally lives in public/, so a
     * trailing "public" segment is stripped.
     *
     * @return string The base URL, with a trailing slash.
     */
    private function derive_base_url(): string {
        $scheme = 'http';

        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

        if (basename($dir) === 'public') {
            $dir = dirname($dir);
        }

        return $scheme . '://' . $host . rtrim($dir, '/') . '/';
    }

    /**
     * The session key under which this installation's state is filed.
     *
     * A PHP session is not private to one application. The session cookie is
     * issued for the whole host (its default path is "/") and the default save
     * path is shared, so every application on the machine reads and writes the
     * same $_SESSION. Filing the state under a digest of the application's own
     * directory makes it unique to this installation, so that a second
     * installation cannot inherit the first one's progress.
     *
     * @return string
     */
    private function get_state_key(): string {
        return md5(APPPATH);
    }

    /**
     * Load wizard state from the session.
     *
     * @return void
     */
    private function load_state(): void {
        $key = $this->get_state_key();

        if (isset($_SESSION['setup_wizard'][$key]) && is_array($_SESSION['setup_wizard'][$key])) {
            $this->state = $_SESSION['setup_wizard'][$key];
        }

        if ($this->has_state_from_a_different_installation() === true) {
            // Discard the whole state: a fresh installation starts at stage one.
            $this->state = [];
        }

        $this->fast_mode = ($this->state['fast_mode'] ?? false) === true;

        $step = (int) ($this->state['step'] ?? 1);

        if ($step < 1 || $step > count($this->steps)) {
            $step = 1;
        }

        $this->step = $step;
    }

    /**
     * Does the session state belong to an application that is no longer here?
     *
     * The database stage is only ever passed once config/database.php names the
     * database that was tested, so state that has got beyond that stage while
     * the file names a different database - or names none at all - was written
     * for an installation that has since been replaced: a fresh download in a
     * folder that was used before, or a setup that was abandoned and then
     * started again from a clean copy. Keeping it would carry an unfinished
     * installation into a new one, skipping the stages that were already
     * passed.
     *
     * The application's URL is deliberately not used for this test. It is the
     * last thing either path writes, so it stays a true statement of "not
     * installed yet" for the whole of the setup.
     *
     * @return bool
     */
    private function has_state_from_a_different_installation(): bool {
        if (empty($this->state['database_file_verified'])) {
            return false;
        }

        $recorded = (string) ($this->state['database']['database'] ?? '');
        $configured = (string) ($this->get_configured_database()['database'] ?? '');

        return ($recorded === '' || $configured !== $recorded);
    }

    /**
     * Dispatch a posted form to the stage that owns it.
     *
     * @return void
     */
    private function handle_post(): void {
        $action = (string) ($_POST['action'] ?? '');
        $posted_token = (string) ($_POST['token'] ?? '');

        if ($action === '' || hash_equals($this->get_token(), $posted_token) === false) {
            $this->notice = 'That request could not be verified. Please try again.';
            return;
        }

        switch ($action) {
            case 'begin':
                $this->start_wizard();
                break;
            case 'use_wizard':
                $this->start_wizard();
                break;
            case 'fast_install':
                if ($this->store_posted_database() === true) {
                    $this->fast_install();
                }
                break;
            case 'retry_probe':
                $this->probe = null;
                $this->step = 1;
                break;
            case 'confirm_base_url':
                $this->confirm_base_url();
                break;
            case 'test_database':
                $this->test_database();
                break;
            case 'restart_database':
                unset($this->state['database_tested'], $this->state['database_file_verified']);
                $this->step = 2;
                break;
            case 'verify_database_file':
                $this->verify_database_file();
                break;
            case 'create_admin':
                $this->create_admin();
                break;
            case 'start_again':
                $this->start_again();
                break;
            default:
                $this->notice = 'Unrecognised request. Please try again.';
                break;
        }
    }

    /**
     * Stage 4: the user has saved BASE_URL to config/config.php.
     *
     * This is the last piece of work in the wizard. There is no form field here
     * - the value is adopted silently - so the only way to know the user did the
     * work is to look at the constant, which has been re-read from config.php at
     * the start of this request.
     *
     * @return void
     */
    private function confirm_base_url(): void {
        if (BASE_URL === '****') {
            $this->step = 4;
            $this->errors['base_url'] = 'BASE_URL is still set to \'****\' in config/config.php. '
                . 'Paste the line above into that file, save it, wait a moment, then click the button again.';
            return;
        }

        $this->state['base_url_confirmed'] = true;
        $this->step = 5;
    }

    /**
     * Stage 2a: test the posted database credentials.
     *
     * The database is refused when it already holds an installation, which is
     * the same rule the automatic path applies. The check is made here rather
     * than at the stage that creates the administrator, because creating one
     * truncates trongate_administrators and trongate_users: by then the other
     * installation's account would already be gone.
     *
     * @return void
     */
    private function test_database(): void {
        $credentials = $this->get_posted_credentials();
        $this->errors = $this->validate_credentials($credentials);

        if ($this->errors !== []) {
            $this->state['database'] = $credentials;
            $this->step = 2;
            return;
        }

        $result = $this->test_credentials($credentials);

        if ($result !== true) {
            $this->errors['database'] = $result;
            $this->state['database'] = $credentials;
            $this->step = 2;
            return;
        }

        $inspection = $this->inspect_database($credentials);

        if ($inspection['installed'] === true) {
            $this->errors['database'] = 'The database "' . $credentials['database'] . '" already holds a '
                . 'Trongate installation, so it has been left untouched. Choose another database name.';
            $this->state['database'] = $credentials;
            $this->step = 2;
            return;
        }

        $this->state['database'] = $credentials;
        $this->state['database_tested'] = true;
        $this->step = 2;
    }

    /**
     * Stage 2b: the user says config/database.php has been saved.
     *
     * Verifies the saved file by connecting with it - the same connection the
     * application itself will make - rather than trusting the form post.
     *
     * @return void
     */
    private function verify_database_file(): void {
        $result = $this->test_saved_config();

        if ($result !== true) {
            $this->errors['database_file'] = $result;
            $this->step = 2;
            return;
        }

        // The credentials are in config/database.php now; the session copy of
        // the password is no longer needed.
        unset($this->state['database']['password']);
        $this->state['database_file_verified'] = true;
        $this->step = 3;
    }

    /**
     * Stage 3: create the administrator account.
     *
     * @return void
     */
    private function create_admin(): void {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm_password = (string) ($_POST['confirm_password'] ?? '');

        $this->errors = $this->validate_admin_account($username, $email, $password, $confirm_password);

        if ($this->errors !== []) {
            $this->state['admin'] = ['username' => $username, 'email' => $email];
            $this->step = 3;
            return;
        }

        $result = $this->create_admin_account($username, $email, $password);

        if ($result !== true) {
            $this->errors['admin'] = $result;
            $this->state['admin'] = ['username' => $username, 'email' => $email];
            $this->step = 3;
            return;
        }

        $this->state['admin'] = ['username' => $username, 'email' => $email];
        $this->state['admin_created'] = true;

        if ($this->fast_mode === true) {
            $this->finish_automatic_installation();
            return;
        }

        $this->step = 4;
    }

    /**
     * Write the application's URL, now that the automatic installation has
     * done everything else.
     *
     * The URL is written last on purpose. While config/config.php holds the
     * placeholder the first-run check stays on, so the wizard keeps appearing at
     * whatever address the developer opens, and a failure leaves a page that can
     * be finished rather than an application that cannot be reached.
     *
     * @return void
     */
    private function finish_automatic_installation(): void {
        if ($this->write_base_url() !== true) {
            // The stage that asks for the line by hand is still available, so
            // that stays open rather than leaving the installation unfinished.
            $this->fast_mode = false;
            $this->state['fast_mode'] = false;
            $this->step = 4;
            $this->notice = 'Your application\'s address could not be saved to config/config.php. '
                . 'Paste the line below into that file, save it, then click Continue.';
            return;
        }

        $this->state['base_url_confirmed'] = true;
        $this->module_deletion_pending = true;
        $this->step = 5;
    }

    /**
     * Stage 5: forget this installation's progress and return to stage one.
     *
     * The final stage is otherwise a dead end. Once the administrator has been
     * created the page offers only the homepage and the removal of this folder,
     * so if the administrator row is later missing from the database - deleted
     * by hand, or the database itself changed - the wizard can report the fact
     * but cannot offer any way back to the stages that would put it right.
     *
     * Starting again clears the wizard's own record of what was done, and
     * nothing else: no file in config/, no table and no row is touched, and the
     * stages that follow re-check the configuration before they are passed.
     *
     * @return void
     */
    private function start_again(): void {
        $this->state = [];
        $this->fast_mode = false;
        $this->step = 1;
    }

    /**
     * Collect the posted database credentials.
     *
     * @return array<string, string>
     */
    private function get_posted_credentials(): array {
        $credentials = [
            'host' => trim((string) ($_POST['host'] ?? '')),
            'port' => trim((string) ($_POST['port'] ?? '')),
            'user' => trim((string) ($_POST['user'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'database' => trim((string) ($_POST['database'] ?? ''))
        ];

        if ($credentials['port'] === '') {
            $credentials['port'] = '3306';
        }

        return $credentials;
    }

    /**
     * Validate database credentials that have been posted.
     *
     * @param array<string, string> $credentials
     * @return array<string, string>
     */
    private function validate_credentials(array $credentials): array {
        $errors = [];

        if ($credentials['host'] === '') {
            $errors['host'] = 'A host is required.';
        }

        if (ctype_digit($credentials['port']) === false || (int) $credentials['port'] < 1 || (int) $credentials['port'] > 65535) {
            $errors['port'] = 'The port must be a number between 1 and 65535.';
        }

        if ($credentials['user'] === '') {
            $errors['user'] = 'A username is required.';
        }

        if ($credentials['database'] === '') {
            $errors['database'] = 'A database name is required.';
        } elseif (preg_match('/^[a-zA-Z0-9_]+$/', $credentials['database']) !== 1) {
            $errors['database'] = 'The database name may only contain letters, numbers and underscores.';
        }

        return $errors;
    }

    /**
     * Validate the posted administrator details.
     *
     * @return array<string, string>
     */
    private function validate_admin_account(string $username, string $email, string $password, string $confirm_password): array {
        $errors = [];

        if (mb_strlen($username) < 2) {
            $errors['username'] = 'The username must be at least two characters long.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'The password must be at least eight characters long.';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'The passwords do not match.';
        }

        return $errors;
    }

    /**
     * Test posted database credentials with a live connection.
     *
     * Connects to the server, creates the database if it is not there, then
     * connects to the database itself and runs a query. A success here means
     * MySQL accepted these credentials - nothing less will do.
     *
     * @param array<string, string> $credentials
     * @return true|string TRUE on success, a message on failure.
     */
    private function test_credentials(array $credentials): bool|string {
        $dsn = 'mysql:host=' . $credentials['host'] . ';port=' . $credentials['port'];

        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database');
            $statement->execute([':database' => $credentials['database']]);

            if ($statement->fetch() === false) {
                $pdo->exec('CREATE DATABASE `' . $credentials['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }

            $pdo = new PDO($dsn . ';dbname=' . $credentials['database'], $credentials['user'], $credentials['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            $pdo->query('SELECT 1');
        } catch (PDOException $e) {
            return 'Unable to connect to the database server with those details. '
                . 'Check the host, port, username and password.';
        }

        return true;
    }

    /**
     * Test the database settings that are in config/database.php right now.
     *
     * The file has to name a database - connecting to a server with no database
     * selected proves nothing - and the connection is then made through the file
     * itself, so a pass means the application's own configuration works.
     *
     * @return true|string TRUE on success, a message on failure.
     */
    private function test_saved_config(): bool|string {
        $config = $GLOBALS['databases']['default'] ?? [];

        if ((string) ($config['database'] ?? '') === '') {
            return 'config/database.php does not name a database yet. Replace its contents with '
                . 'the code above, save the file, wait a moment, then try again.';
        }

        try {
            $pdo = $this->get_pdo_connection();
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            return 'config/database.php does not connect yet. Check that the file has been '
                . 'saved with the code above, wait a moment, then try again.';
        }

        return true;
    }

    /**
     * The database that the wizard is working with.
     *
     * The wizard's own record of the database it tested comes first, because the
     * first half of the database stage is drawn before config/database.php has
     * been written and the loaded copy of that file therefore names nothing.
     *
     * @return string
     */
    private function get_connected_database(): string {
        $posted = (string) ($this->state['database']['database'] ?? '');

        if ($posted !== '') {
            return $posted;
        }

        return (string) ($GLOBALS['databases']['default']['database'] ?? '');
    }

    /**
     * Is the database stage showing its second half?
     *
     * @return bool
     */
    private function is_database_file_stage(): bool {
        return empty($this->state['database_tested']) === false;
    }

    /**
     * Re-test the posted credentials, for the benefit of the view.
     *
     * Run on every draw, so the tick can only appear on a response whose own
     * request made a successful connection.
     *
     * @return bool
     */
    private function is_database_verified(): bool {
        if (empty($this->state['database_tested'])) {
            return false;
        }

        $credentials = $this->state['database'] ?? [];

        if (isset($credentials['host']) === false) {
            return false;
        }

        $this->database_verified = ($this->test_credentials($credentials) === true);

        return $this->database_verified;
    }

    /**
     * The administrator username that was created, or is being entered.
     *
     * @return string
     */
    private function get_admin_username(): string {
        return (string) ($this->state['admin']['username'] ?? '');
    }

    /**
     * The administrator email address that was entered.
     *
     * @return string
     */
    private function get_admin_email(): string {
        return (string) ($this->state['admin']['email'] ?? '');
    }

    /**
     * Is this request the one that should celebrate?
     *
     * @return bool
     */
    private function is_celebrating(): bool {
        return $this->celebrate;
    }

    /**
     * The token that every form on the page carries.
     *
     * The wizard has no CSRF library to lean on - it runs before routing - so
     * it issues a session token of its own and checks it on every post.
     *
     * @return string
     */
    private function get_token(): string {
        if (empty($this->state['token'])) {
            $this->state['token'] = bin2hex(random_bytes(16));
        }

        return $this->state['token'];
    }

    /**
     * Draw the hidden token field that every wizard form carries.
     *
     * @return string
     */
    private function draw_token_field(): string {
        return '<input type="hidden" name="token" value="' . out($this->get_token()) . '">';
    }

    /**
     * The name of the view file for the current stage.
     *
     * @return string
     */
    private function get_view_name(): string {
        $names = [
            1 => 'welcome',
            2 => 'database',
            3 => 'admin',
            4 => 'base_url',
            5 => 'finalize'
        ];

        return $names[$this->step];
    }

    /**
     * Confirm that the administrator account described by the session exists.
     *
     * Read from the database on every draw, so a tick can never outlive the
     * request that earned it.
     *
     * @return bool
     */
    private function is_admin_verified(): bool {
        $this->admin_verified = false;

        if (empty($this->state['admin_created'])) {
            return false;
        }

        $username = (string) ($this->state['admin']['username'] ?? '');

        if ($username === '') {
            return false;
        }

        try {
            $pdo = $this->get_pdo_connection();
            $statement = $pdo->prepare('SELECT id FROM trongate_administrators WHERE username = :username');
            $statement->execute([':username' => $username]);
            $this->admin_verified = ($statement->fetch() !== false);
        } catch (Throwable $e) {
            $this->admin_verified = false;
        }

        return $this->admin_verified;
    }

    /**
     * Create the framework's tables and the administrator account.
     *
     * @return true|string TRUE on success, a message on failure.
     */
    private function create_admin_account(string $username, string $email, string $password): bool|string {
        $sql_path = __DIR__ . '/sql/setup.sql';

        if (is_file($sql_path) === false) {
            return 'The setup SQL file is missing from modules/setup/sql/.';
        }

        try {
            $pdo = $this->get_pdo_connection();

            // The same rule the database stage applies. Checked again here,
            // immediately before the truncation, because these two statements
            // are the ones that would destroy another installation's account.
            $database = (string) ($this->state['database']['database'] ?? $this->get_connected_database());

            if ($this->has_administrator_rows($pdo, $database) === true) {
                return 'The database "' . $database . '" already holds a Trongate installation, so its '
                    . 'account has been left untouched. Choose another database name.';
            }

            $pdo->exec((string) file_get_contents($sql_path));

            // Truncate so that the wizard can be safely re-run.
            $pdo->exec('TRUNCATE TABLE trongate_administrators');
            $pdo->exec('TRUNCATE TABLE trongate_users');

            $statement = $pdo->prepare('INSERT INTO trongate_users (code, user_level_id) VALUES (:code, 1)');
            $statement->execute([':code' => bin2hex(random_bytes(16))]);
            $user_id = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO trongate_administrators (trongate_user_id, username, email, password, active) '
                . 'VALUES (:user_id, :username, :email, :password, 1)'
            );
            $statement->execute([
                ':user_id' => $user_id,
                ':username' => $username,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 11])
            ]);
        } catch (Throwable $e) {
            return 'The administrator account could not be created: ' . $e->getMessage();
        }

        return true;
    }

    /**
     * Build a database connection from config/database.php.
     *
     * @return PDO
     *
     * @throws PDOException If the connection fails.
     */
    private function get_pdo_connection(): PDO {
        // The automatic installation writes config/database.php and then keeps
        // working in the same session, where the loaded copy of that file is
        // already stale. The credentials it has just saved are used instead.
        if ($this->fast_mode === true && isset($this->state['database']['host'])) {
            return $this->connect_to_database($this->state['database']);
        }

        $config = $GLOBALS['databases']['default'];
        $dsn = 'mysql:host=' . $config['host']
            . ';port=' . ($config['port'] ?? '3306')
            . ';dbname=' . $config['database'];

        return new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
    }

    /**
     * The values that the database form is drawn with.
     *
     * @return array<string, string>
     */
    private function get_database_defaults(): array {
        $defaults = $this->get_configured_database();

        if ($defaults['database'] === '') {
            $defaults['database'] = $this->suggest_database_name();
        }

        if (isset($this->state['database']) && is_array($this->state['database'])) {
            $defaults = array_merge($defaults, $this->state['database']);
        }

        return $defaults;
    }

    /**
     * The database settings that config/database.php already holds.
     *
     * The developer's own configuration is the only sensible place to look for
     * the details of their database server, so it is read here rather than
     * assumed. ignition.php loads that file and publishes the array as
     * $GLOBALS['databases'] before the wizard can run, so nothing is re-read
     * from disk on this request.
     *
     * @return array<string, string>
     */
    private function get_configured_database(): array {
        $defaults = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'user' => 'root',
            'password' => '',
            'database' => ''
        ];

        $configured = $GLOBALS['databases']['default'] ?? [];

        if (is_array($configured) === false) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (isset($configured[$key]) && is_scalar($configured[$key])) {
                $defaults[$key] = (string) $configured[$key];
            }
        }

        return $defaults;
    }

    /**
     * Suggest a database name, based on the application's base URL.
     *
     * @return string
     */
    private function suggest_database_name(): string {
        $path = trim((string) parse_url($this->base_url, PHP_URL_PATH), '/');

        if ($path === '') {
            return 'trongate_app';
        }

        $parts = explode('/', $path);

        return strtolower(str_replace('-', '_', end($parts)));
    }

    /**
     * Build the contents of config/database.php, for the user to paste.
     *
     * @return string
     */
    private function get_database_config_code(): string {
        $credentials = $this->get_database_defaults();
        $code = "<?php\n\$databases = [\n    'default' => [\n";

        foreach (['host', 'port', 'user', 'password', 'database'] as $key) {
            $value = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $credentials[$key]);
            $code .= "        '" . $key . "' => '" . $value . "',\n";
        }

        $code .= "    ]\n];\n";

        return $code;
    }

    /**
     * Entry point when the wizard is reached by ordinary routing, at /setup.
     *
     * @return void
     */
    public function index(): void {
        $this->run();
    }

    /**
     * The address that every form in the wizard posts back to.
     *
     * Before installation the wizard answers every URL, so this value only has
     * to be right once config/config.php holds a real BASE_URL - after which
     * the wizard is reached by routing and has to be posted back to by name.
     *
     * @return string
     */
    private function get_wizard_url(): string {
        return rtrim($this->base_url, '/') . '/setup';
    }

    /**
     * The application URL that has been derived from the request.
     *
     * @return string
     */
    private function get_base_url(): string {
        return $this->base_url;
    }

    /**
     * The define() line for config/config.php.
     *
     * @return string
     */
    private function get_base_url_code(): string {
        return "define('BASE_URL', '" . $this->base_url . "');";
    }

    /**
     * Field level error messages.
     *
     * @return array<string, string>
     */
    private function get_errors(): array {
        return $this->errors;
    }

    /**
     * A single field's error message, or an empty string.
     *
     * @return string
     */
    private function get_error(string $field): string {
        return $this->errors[$field] ?? '';
    }

    /**
     * Draw the error block, if there is anything to report.
     *
     * @return string
     */
    private function draw_errors(): string {
        $messages = array_values($this->errors);

        if ($this->notice !== '') {
            $messages[] = $this->notice;
        }

        if ($messages === []) {
            return '';
        }

        $html = '<div class="validation-errors"><ul>';

        foreach ($messages as $message) {
            $html .= '<li>' . out($message) . '</li>';
        }

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Draw the probe's findings as quiet information, not as a failure.
     *
     * Nothing has gone wrong at this point: the wizard is simply describing
     * what it found. The block is left out entirely when there is a message
     * above it, so that nothing is said twice.
     *
     * @return string
     */
    private function draw_probe_reasons(): string {
        $reasons = $this->get_probe_reasons();

        if ($reasons === [] || $this->errors !== [] || $this->notice !== '') {
            return '';
        }

        $html = '<ul class="setup-reasons muted">';

        foreach ($reasons as $reason) {
            $html .= '<li>' . out($reason) . '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Draw the step indicator.
     *
     * @return string
     */
    private function draw_steps_indicator(): string {
        $steps = $this->steps;
        $current = $this->step;

        // The automatic installation is one action and one form, so drawing the
        // five stages of the step-by-step wizard in front of it would suggest
        // work that the developer is never asked to do.
        if ($this->fast_mode === true) {
            $steps = [1 => 'Install', 2 => 'Your Account'];
            $current = ($this->step >= 5) ? 3 : 2;
        }

        $html = '<div class="steps">';

        foreach ($steps as $number => $title) {
            $step_class = 'step';

            if ($number === $current) {
                $step_class .= ' active';
            } elseif ($number < $current) {
                $step_class .= ' done';
            }

            $html .= '<div class="' . $step_class . '">';
            $html .= '<span class="step-number">' . $number . '</span>';
            $html .= '<span class="step-title">' . out($title) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Draw the tick that confirms a test was run and passed.
     *
     * Markup matches the indicator that Trongate MX draws; the animation is
     * pure CSS in public/css/trongate.css. The server only ever draws this
     * after a live check has returned true.
     *
     * @return string
     */
    private function draw_tick(): string {
        $html = '<div class="mx-trigger" data-draw-tick="true">';
        $html .= '<svg version="1.1" id="mx-tick" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37 37" xml:space="preserve">';
        $html .= '<path class="mx-circ path" style="fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10" d="M30.5,6.5L30.5,6.5c6.6,6.6,6.6,17.4,0,24l0,0c-6.6,6.6-17.4,6.6-24,0l0,0c-6.6-6.6-6.6-17.4,0-24l0,0C13.1-0.2,23.9-0.2,30.5,6.5z"></path>';
        $html .= '<polyline class="mx-tick path" style="fill:none;stroke:#007700;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10" points="11.6,20 15.9,24.2 26.4,13.8"></polyline>';
        $html .= '</svg>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Draw the help link that appears at the foot of every stage.
     *
     * @return string
     */
    private function draw_help_link(): string {
        $html = '<p class="help-link">Having difficulties? Need help? ';
        $html .= '<a href="#" id="setup-help-link">CLICK HERE</a>';
        $html .= '</p>';
        $html .= '<div class="instructions cloak" id="setup-help-message">';
        $html .= '<p>In the world of PHP frameworks, nothing is more disheartening than when someone '
            . 'begins their journey but fails with the initial setup.</p>';
        $html .= '<p>So here is a promise: nobody will ever have to abandon Trongate because they '
            . 'cannot get past the installation phase. Not even one person.</p>';
        $html .= '<p>If you are stuck, email <strong><span id="setup-help-email"></span></strong> and '
            . 'you will get a personal reply. You have my word.</p>';
        $html .= '<p>Dave Connelly (founder)</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Should this request celebrate, or has it already celebrated?
     *
     * The celebration is drawn once, on arriving at the final stage.
     *
     * @return bool
     */
    private function should_celebrate(): bool {
        if ($this->step !== 5) {
            return false;
        }

        if (empty($this->state['celebrated'])) {
            $this->state['celebrated'] = true;
            return true;
        }

        return false;
    }

    // ────────────────────────────────────────────────────────────────────
    // The automatic installation
    // ────────────────────────────────────────────────────────────────────

    /**
     * Leave the automatic installation and take up the step-by-step wizard.
     *
     * @return void
     */
    private function start_wizard(): void {
        $this->state['fast_mode'] = false;
        $this->fast_mode = false;
        $this->step = 2;
    }

    /**
     * Adopt database details that were posted with the automatic installation.
     *
     * The first stage only offers these fields when the settings it found did
     * not work, so they are usually absent. Where they are present they are
     * taken as the developer's answer and the probe is thrown away, so that it
     * is made again against what they typed rather than against what was found.
     *
     * @return bool TRUE when the installation should go ahead.
     */
    private function store_posted_database(): bool {
        if (isset($_POST['host']) === false) {
            return true;
        }

        $credentials = $this->get_posted_credentials();
        $this->state['database'] = $credentials;
        $this->probe = null;

        $this->errors = $this->validate_credentials($credentials);

        if ($this->errors !== []) {
            $this->step = 1;
            return false;
        }

        return true;
    }

    /**
     * Install the application without asking anything that can be detected.
     *
     * Saves the database settings, creates the database, runs the framework's
     * SQL, and then hands over to the administrator form. The application's URL
     * is not written here: it is written once the administrator exists, so that
     * a failure in between leaves the first-run check on. If any part of it
     * fails the developer is returned to the first stage with a reason and the
     * wizard's own stages remain available.
     *
     * @return void
     */
    private function fast_install(): void {
        $this->step = 1;
        $this->probe = null;

        $probe = $this->get_install_probe();

        if ($probe['ok'] !== true) {
            // The reason the probe gives is more use than a general apology, so
            // it becomes the message and the muted list steps aside for it.
            $this->notice = (string) ($probe['reasons'][0]
                ?? 'Something on this machine still needs attention before Trongate can install itself.');
            return;
        }

        $credentials = $this->get_database_defaults();
        $inspection = $this->inspect_database($credentials);

        if ($inspection['connected'] !== true) {
            $this->notice = 'No database server answered at ' . $credentials['host'] . ':'
                . $credentials['port'] . ' for the user "' . $credentials['user'] . '". '
                . 'Check the details below.';
            return;
        }

        if ($inspection['installed'] === true) {
            $this->notice = 'The database "' . $credentials['database'] . '" already holds a Trongate '
                . 'installation, so it has been left untouched. Choose another database name below.';
            return;
        }

        // Written only now: a database that already holds an installation is
        // refused above, and config/database.php is left pointing wherever the
        // developer had it.
        if ($this->write_database_config() !== true) {
            $this->notice = 'The database settings could not be saved to config/database.php. '
                . 'The step-by-step setup writes them by hand instead.';
            return;
        }

        if ($inspection['exists'] === false) {
            $result = $this->create_database($credentials);

            if ($result !== true) {
                $this->notice = $result;
                return;
            }
        }

        $result = $this->run_setup_sql($credentials);

        if ($result !== true) {
            $this->notice = $result;
            return;
        }

        $this->state['database'] = $credentials;
        $this->state['database_tested'] = true;
        $this->state['database_file_verified'] = true;
        $this->state['fast_mode'] = true;
        $this->fast_mode = true;
        $this->step = 3;
    }

    /**
     * Inspect the environment and decide whether the application can install
     * itself without help.
     *
     * @return array<string, mixed>
     */
    private function get_install_probe(): array {
        if ($this->probe !== null) {
            return $this->probe;
        }

        $credentials = $this->get_database_defaults();
        $inspection = $this->inspect_database($credentials);

        $probe = [
            'ok' => true,
            'reasons' => [],
            'local' => $this->is_local_request(),
            'config_writable' => is_writable(APPPATH . 'config'),
            'module_deletable' => $this->can_delete_module_folder(),
            'connected' => $inspection['connected'],
            'database_installed' => $inspection['installed'],
            'database' => $credentials,
            'os' => $this->get_os_label(),
            'container' => $this->is_container_environment(),
            'wsl' => $this->is_wsl_environment(),
            'command' => ''
        ];

        if ($probe['local'] !== true) {
            $probe['reasons'][] = 'This request did not come from the local machine, so the '
                . 'automatic installation is not offered.';
        }

        if ($probe['config_writable'] !== true) {
            $probe['reasons'][] = 'PHP is not allowed to write to the application\'s config folder, '
                . 'so the application URL and the database settings cannot be saved automatically.';
        }

        if ($probe['module_deletable'] !== true) {
            $probe['reasons'][] = 'PHP is not allowed to delete the setup module, so the installation '
                . 'could not be completed automatically.';
        }

        if ($inspection['connected'] !== true) {
            $probe['reasons'][] = 'No database server answered at ' . $credentials['host'] . ':'
                . $credentials['port'] . ' for the user "' . $credentials['user'] . '".';
        } elseif ($inspection['installed'] === true) {
            $probe['reasons'][] = 'The database "' . $credentials['database'] . '" already contains '
                . 'a Trongate installation, so it will not be overwritten.';
        }

        if (BASE_URL !== '****' && rtrim(BASE_URL, '/') !== rtrim($this->derive_base_url(), '/')) {
            $probe['reasons'][] = 'The application is being served from ' . $this->derive_base_url()
                . ', which does not match BASE_URL in config/config.php.';
        }

        $probe['ok'] = ($probe['reasons'] === []);

        if ($probe['config_writable'] !== true || $probe['module_deletable'] !== true) {
            $probe['command'] = $this->get_permission_command();
        }

        $this->probe = $probe;

        return $probe;
    }

    /**
     * Is the request being made from the machine running PHP?
     *
     * The automatic installation writes BASE_URL and the database credentials
     * to disk and then deletes a folder of code, so it is only ever offered to
     * a local request: loopback, or a private network address.
     *
     * @return bool
     */
    private function is_local_request(): bool {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if ($ip === '') {
            // No socket information (a command line run of the wizard).
            return true;
        }

        if ($ip === '::1' || str_starts_with($ip, '127.')) {
            return true;
        }

        $public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return ($public === false);
    }

    /**
     * The name of the operating system that PHP is running on.
     *
     * @return string
     */
    private function get_os_label(): string {
        $labels = [
            'Darwin' => 'macOS',
            'Windows' => 'Windows',
            'Linux' => 'Linux',
            'BSD' => 'BSD',
            'Solaris' => 'Solaris'
        ];

        $label = $labels[PHP_OS_FAMILY] ?? PHP_OS_FAMILY;

        if ($label === 'Linux' && $this->is_wsl_environment() === true) {
            $label = 'Windows (through WSL)';
        }

        return $label;
    }

    /**
     * Is PHP running inside Windows Subsystem for Linux?
     *
     * @return bool
     */
    private function is_wsl_environment(): bool {
        return str_contains(strtolower(php_uname('r')), 'microsoft');
    }

    /**
     * Is PHP running inside a container?
     *
     * @return bool
     */
    private function is_container_environment(): bool {
        return is_file('/.dockerenv') || is_file('/run/.containerenv');
    }

    /**
     * The command that would give PHP the permissions the shortcut needs.
     *
     * The command is only ever displayed. It is never executed here: PHP cannot
     * change the ownership of files it does not own, and running a shell command
     * from the setup wizard would be a far bigger risk than the inconvenience it
     * saves.
     *
     * @return string
     */
    private function get_permission_command(): string {
        if (PHP_OS_FAMILY === 'Windows') {
            // There are no POSIX permissions to change.
            return '';
        }

        $sudo = '';

        if (@fileowner(APPPATH) === 0) {
            $sudo = 'sudo ';
        }

        // The whole application folder. The installation itself only writes to
        // config, modules and engine, but Flo's code generators and the
        // framework updater write across the installation, so one command that
        // covers everything is more useful than one that leaves the developer
        // running it again the moment a generator reaches a new folder.
        return $sudo . 'chmod -R 777 ' . $this->shell_quote(rtrim(APPPATH, '/'));
    }

    /**
     * Quote a path for a POSIX shell.
     *
     * @param string $path
     * @return string
     */
    private function shell_quote(string $path): string {
        return "'" . str_replace("'", "'\\''", $path) . "'";
    }

    /**
     * Can this module be deleted with the permissions PHP has right now?
     *
     * @return bool
     */
    private function can_delete_module_folder(): bool {
        if (is_dir(__DIR__) === false) {
            return false;
        }

        if (is_writable(dirname(__DIR__)) === false) {
            return false;
        }

        return $this->is_dir_tree_writable(__DIR__);
    }

    /**
     * Is every directory in this tree writable?
     *
     * @param string $dir
     * @return bool
     */
    private function is_dir_tree_writable(string $dir): bool {
        if (is_writable($dir) === false) {
            return false;
        }

        $items = @scandir($dir);

        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path) === true && is_link($path) === false) {
                if ($this->is_dir_tree_writable($path) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Take the first-run check out of engine/Core.php.
     *
     * The check is one comparison, but it is a comparison that asks "is this a
     * fresh download?" on every request for as long as the application runs.
     * The application has just been installed, so the answer is known and the
     * question can be removed from the file.
     *
     * The two blocks are matched exactly and the removal is all-or-nothing: if
     * either block is not found, or the result would not parse, the file is
     * left untouched. That matters because engine/Core.php is framework code
     * and may have been reformatted by a later release.
     *
     * @return bool
     */
    private function remove_setup_gate(): bool {
        $path = APPPATH . 'engine/Core.php';

        if (is_file($path) === false || is_writable($path) === false) {
            return false;
        }

        $contents = (string) file_get_contents($path);

        if ($contents === '') {
            return false;
        }

        // Each block begins with the blank line that separates it from what
        // goes before it, so that removing the block leaves no double gap.
        $gate = <<<'PHP'

        // One constant comparison: no string function, no filesystem call. It is
        // true only while config/config.php still holds the placeholder, so once
        // the application is installed this costs a single test that cannot fail.
        if (BASE_URL === '****') {
            $this->attempt_invoke_setup_wizard();
        }

PHP;

        $method = <<<'PHP'

    /**
     * Render the first-run setup wizard and end the request.
     *
     * @return never
     */
    private function attempt_invoke_setup_wizard(): void {
        require APPPATH . 'modules/setup/Setup.php';
        $setup = new Setup;
        $setup->run();
        die();
    }

PHP;

        foreach ([$gate, $method] as $block) {
            if (str_contains($contents, $block) === false) {
                return false;
            }
        }

        $contents = str_replace([$gate, $method], '', $contents);

        if (str_contains($contents, 'attempt_invoke_setup_wizard') === true) {
            return false;
        }

        try {
            token_get_all($contents, TOKEN_PARSE);
        } catch (ParseError $e) {
            return false;
        }

        return $this->write_file($path, $contents);
    }

    /**
     * Delete this module, so that the wizard cannot run again.
     *
     * @return bool
     */
    private function delete_module_folder(): bool {
        return $this->delete_path(__DIR__);
    }

    /**
     * Delete a file, or a directory and everything in it.
     *
     * @param string $path
     * @return bool
     */
    private function delete_path(string $path): bool {
        if (is_link($path) === true || is_file($path) === true) {
            return @unlink($path);
        }

        if (is_dir($path) === false) {
            return true;
        }

        $items = @scandir($path);

        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if ($this->delete_path($path . '/' . $item) === false) {
                return false;
            }
        }

        return @rmdir($path);
    }

    /**
     * Add a block of HTML to the foot of a page that has already been drawn.
     *
     * @param string $html
     * @param string $message
     * @return string
     */
    private function append_alert(string $html, string $message): string {
        $block = '<div class="alert alert-error" style="max-width: 640px; margin: 24px auto;"><p>'
            . $message . '</p></div>';

        return str_replace('</body>', $block . "\n</body>", $html);
    }

    /**
     * Save the application's URL to config/config.php.
     *
     * @return bool
     */
    private function write_base_url(): bool {
        $path = APPPATH . 'config/config.php';
        $contents = is_file($path) ? (string) file_get_contents($path) : '';

        if ($contents === '') {
            return false;
        }

        $pattern = "/define\\(\\s*'BASE_URL'\\s*,\\s*'[^']*'\\s*\\)\\s*;/";

        if (preg_match($pattern, $contents) !== 1) {
            return false;
        }

        $value = str_replace(['\\', "'"], ['\\\\', "\\'"], $this->base_url);

        $contents = (string) preg_replace_callback($pattern, function () use ($value) {
            return "define('BASE_URL', '" . $value . "');";
        }, $contents, 1);

        return $this->write_file($path, $contents);
    }

    /**
     * Save the database settings to config/database.php.
     *
     * @return bool
     */
    private function write_database_config(): bool {
        return $this->write_file(APPPATH . 'config/database.php', $this->get_database_config_code());
    }

    /**
     * Write a file by writing a temporary file alongside it and renaming it.
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
    private function write_file(string $path, string $contents): bool {
        $temp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (@file_put_contents($temp, $contents) === false) {
            return false;
        }

        if (@rename($temp, $path) === false) {
            @unlink($temp);
            return false;
        }

        if (function_exists('opcache_invalidate') === true) {
            @opcache_invalidate($path, true);
        }

        return true;
    }

    /**
     * Look at the database without changing it.
     *
     * @param array<string, string> $credentials
     * @return array<string, bool>
     */
    private function inspect_database(array $credentials): array {
        $result = ['connected' => false, 'exists' => false, 'installed' => false];
        $dsn = 'mysql:host=' . $credentials['host'] . ';port=' . $credentials['port'];

        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            $result['connected'] = true;

            $statement = $pdo->prepare(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database'
            );
            $statement->execute([':database' => $credentials['database']]);
            $result['exists'] = ($statement->fetch() !== false);

            if ($result['exists'] === true) {
                $result['installed'] = $this->has_administrator_rows($pdo, $credentials['database']);
            }
        } catch (Throwable $e) {
            $result['connected'] = false;
        }

        return $result;
    }

    /**
     * Does this database already hold an installation?
     *
     * Creating the administrator account truncates trongate_administrators and
     * trongate_users, so a database that already has an administrator must not
     * be treated as empty.
     *
     * @param PDO $pdo
     * @param string $database
     * @return bool
     */
    private function has_administrator_rows(PDO $pdo, string $database): bool {
        try {
            $statement = $pdo->query('SELECT COUNT(*) AS total FROM `' . $database . '`.trongate_administrators');
            $row = $statement->fetch();
        } catch (Throwable $e) {
            // No such table: nothing has been installed here.
            return false;
        }

        return ($row !== false && (int) $row['total'] > 0);
    }

    /**
     * Create the database.
     *
     * @param array<string, string> $credentials
     * @return true|string TRUE on success, a message on failure.
     */
    private function create_database(array $credentials): bool|string {
        $dsn = 'mysql:host=' . $credentials['host'] . ';port=' . $credentials['port'];

        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            $pdo->exec('CREATE DATABASE `' . $credentials['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            return 'The database "' . $credentials['database'] . '" could not be created: ' . $e->getMessage();
        }

        return true;
    }

    /**
     * Run the framework's SQL against the database.
     *
     * @param array<string, string> $credentials
     * @return true|string TRUE on success, a message on failure.
     */
    private function run_setup_sql(array $credentials): bool|string {
        $sql_path = __DIR__ . '/sql/setup.sql';

        if (is_file($sql_path) === false) {
            return 'The setup SQL file is missing from modules/setup/sql/.';
        }

        try {
            $pdo = $this->connect_to_database($credentials);
            $pdo->exec((string) file_get_contents($sql_path));
        } catch (Throwable $e) {
            return 'The framework tables could not be created: ' . $e->getMessage();
        }

        return true;
    }

    /**
     * Connect to a named database with a set of credentials.
     *
     * @param array<string, string> $credentials
     * @return PDO
     *
     * @throws PDOException If the connection fails.
     */
    private function connect_to_database(array $credentials): PDO {
        $dsn = 'mysql:host=' . $credentials['host']
            . ';port=' . $credentials['port']
            . ';dbname=' . $credentials['database'];

        return new PDO($dsn, $credentials['user'], $credentials['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
    }

    /**
     * Can this request install the application without help?
     *
     * @return bool
     */
    private function can_install_automatically(): bool {
        $probe = $this->get_install_probe();
        return $probe['ok'] === true;
    }

    /**
     * Why the automatic installation is not available, as a list of sentences.
     *
     * @return array<int, string>
     */
    private function get_probe_reasons(): array {
        $probe = $this->get_install_probe();
        return $probe['reasons'];
    }

    /**
     * The permission command to display, or an empty string where none applies.
     *
     * @return string
     */
    private function get_probe_command(): string {
        $probe = $this->get_install_probe();
        return $probe['command'];
    }

    /**
     * The operating system that PHP is running on, for display.
     *
     * @return string
     */
    private function get_probe_os(): string {
        $probe = $this->get_install_probe();
        return $probe['os'];
    }

    /**
     * Is PHP running inside a container?
     *
     * @return bool
     */
    private function is_container(): bool {
        $probe = $this->get_install_probe();
        return $probe['container'] === true;
    }

    /**
     * Is PHP running inside Windows Subsystem for Linux?
     *
     * @return bool
     */
    private function is_wsl(): bool {
        $probe = $this->get_install_probe();
        return $probe['wsl'] === true;
    }

    /**
     * The database name that the automatic installation would use.
     *
     * @return string
     */
    private function get_probe_database_name(): string {
        $probe = $this->get_install_probe();
        return (string) $probe['database']['database'];
    }

    /**
     * The database name to offer in the fields on the first stage.
     *
     * Where the database that was found already holds an installation, an empty
     * field is offered instead: repeating a name that has just been refused
     * would only invite the same answer twice.
     *
     * @return string
     */
    private function get_offered_database_name(): string {
        $probe = $this->get_install_probe();

        if ($probe['database_installed'] === true) {
            return '';
        }

        return (string) $probe['database']['database'];
    }

    /**
     * The database host that the automatic installation would use.
     *
     * @return string
     */
    private function get_probe_database_host(): string {
        $probe = $this->get_install_probe();
        return (string) $probe['database']['host'];
    }

    /**
     * The database port that the automatic installation would use.
     *
     * @return string
     */
    private function get_probe_database_port(): string {
        $probe = $this->get_install_probe();
        return (string) $probe['database']['port'];
    }

    /**
     * The database user that the automatic installation would use.
     *
     * @return string
     */
    private function get_probe_database_user(): string {
        $probe = $this->get_install_probe();
        return (string) $probe['database']['user'];
    }

    /**
     * The host, port and user that the automatic installation would use.
     *
     * @return string
     */
    private function get_probe_database_server(): string {
        $probe = $this->get_install_probe();
        $database = $probe['database'];

        return $database['host'] . ':' . $database['port'] . ' (user: ' . $database['user'] . ')';
    }

    /**
     * Is this run of the wizard the automatic installation?
     *
     * @return bool
     */
    private function is_fast_mode(): bool {
        return $this->fast_mode;
    }

    /**
     * Did this request come from the machine that is running PHP?
     *
     * @return bool
     */
    private function is_local(): bool {
        return $this->is_local_request();
    }

    /**
     * The address that the page's silent permission check asks.
     *
     * @return string
     */
    private function get_status_url(): string {
        return $this->get_wizard_url() . '?setup_status=1';
    }

    /**
     * Has this module been removed as part of the automatic installation?
     *
     * @return bool
     */
    private function is_module_deleted(): bool {
        return $this->module_deletion_expected;
    }

}
