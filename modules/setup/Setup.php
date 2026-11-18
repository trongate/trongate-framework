<?php
/**
 * First-run setup wizard.
 *
 * This class is deliberately not a Trongate module class and does not extend
 * Trongate. It is invoked from Core::attempt_invoke_setup_wizard(), which runs
 * before routing exists. Consequently there are no modules, no validation
 * library, no CSRF tokens and no Trongate MX available here - everything is
 * plain PHP, drawn as a single page, with every form posting back to the
 * address that drew it.
 *
 * Nothing in this class writes to the filesystem. Where a file has to change
 * (config/config.php, config/database.php) the wizard prints the code to paste
 * and then verifies the result with a live check on the next request.
 */
class Setup {

    /**
     * The stages of the wizard.
     *
     * @var array<int, string>
     */
    private array $steps = [
        1 => 'Welcome',
        2 => 'Base URL',
        3 => 'Database',
        4 => 'Admin',
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
     * Run the wizard: work out the request, then draw the current stage.
     *
     * @return void
     */
    public function run(): void {
        $this->base_url = $this->get_assumed_base_url();
        $this->load_state();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_post();
        }

        $this->apply_state_guards();
        $this->celebrate = $this->should_celebrate();

        // Make sure a token exists before the state is written, so that the
        // token in the forms matches the token in the session.
        $this->get_token();

        $this->state['step'] = $this->step;
        $_SESSION['setup_wizard'] = $this->state;

        require __DIR__ . '/views/setup_template.php';
    }

    /**
     * Keep the stage honest: a stage cannot be drawn unless the stage that
     * feeds it has actually been completed.
     *
     * @return void
     */
    private function apply_state_guards(): void {
        if ($this->step >= 4 && empty($this->state['database_file_verified'])) {
            $this->step = 3;
        }

        if ($this->step >= 5 && empty($this->state['admin_created'])) {
            $this->step = 4;
        }
    }

    /**
     * Work out the application's base URL from the request.
     *
     * SCRIPT_NAME is the URL of the script that is actually executing, after
     * rewriting and after any server Alias, which is exactly the question being
     * asked here. The application's script normally lives in public/, so a
     * trailing "public" segment is stripped.
     *
     * @return string The base URL, with a trailing slash.
     */
    private function get_assumed_base_url(): string {
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
     * Load wizard state from the session.
     *
     * @return void
     */
    private function load_state(): void {
        if (isset($_SESSION['setup_wizard']) && is_array($_SESSION['setup_wizard'])) {
            $this->state = $_SESSION['setup_wizard'];
        }

        $step = (int) ($this->state['step'] ?? 1);

        if ($step < 1 || $step > count($this->steps)) {
            $step = 1;
        }

        $this->step = $step;
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
                $this->step = 2;
                break;
            case 'confirm_base_url':
                $this->confirm_base_url();
                break;
            case 'test_database':
                $this->test_database();
                break;
            case 'restart_database':
                unset($this->state['database_tested'], $this->state['database_file_verified']);
                $this->step = 3;
                break;
            case 'verify_database_file':
                $this->verify_database_file();
                break;
            case 'create_admin':
                $this->create_admin();
                break;
            default:
                $this->notice = 'Unrecognised request. Please try again.';
                break;
        }
    }

    /**
     * Stage 2: the user has saved BASE_URL to config/config.php.
     *
     * There is no form field here - the value is adopted silently - so the
     * only way to know the user did the work is to look at the constant, which
     * has been re-read from config.php at the start of this request.
     *
     * @return void
     */
    private function confirm_base_url(): void {
        if (BASE_URL === '****') {
            $this->step = 2;
            $this->errors['base_url'] = 'BASE_URL is still set to \'****\' in config/config.php. '
                . 'Paste the line above into that file, save it, wait a moment, then click the button again.';
            return;
        }

        $this->step = 3;
    }

    /**
     * Stage 3a: test the posted database credentials.
     *
     * @return void
     */
    private function test_database(): void {
        $credentials = $this->get_posted_credentials();
        $this->errors = $this->validate_credentials($credentials);

        if ($this->errors !== []) {
            $this->state['database'] = $credentials;
            $this->step = 3;
            return;
        }

        $result = $this->test_credentials($credentials);

        if ($result !== true) {
            $this->errors['database'] = $result;
            $this->state['database'] = $credentials;
            $this->step = 3;
            return;
        }

        $this->state['database'] = $credentials;
        $this->state['database_tested'] = true;
        $this->step = 3;
    }

    /**
     * Stage 3b: the user says config/database.php has been saved.
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
            $this->step = 3;
            return;
        }

        // The credentials are in config/database.php now; the session copy of
        // the password is no longer needed.
        unset($this->state['database']['password']);
        $this->state['database_file_verified'] = true;
        $this->step = 4;
    }

    /**
     * Stage 4: create the administrator account.
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
            $this->step = 4;
            return;
        }

        $result = $this->create_admin_account($username, $email, $password);

        if ($result !== true) {
            $this->errors['admin'] = $result;
            $this->state['admin'] = ['username' => $username, 'email' => $email];
            $this->step = 4;
            return;
        }

        $this->state['admin'] = ['username' => $username, 'email' => $email];
        $this->state['admin_created'] = true;
        $this->step = 5;
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
     * The database that config/database.php names.
     *
     * @return string
     */
    private function get_connected_database(): string {
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
            2 => 'base_url',
            3 => 'database',
            4 => 'admin',
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
        $defaults = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'user' => 'root',
            'password' => '',
            'database' => $this->suggest_database_name()
        ];

        if (isset($this->state['database']) && is_array($this->state['database'])) {
            $defaults = array_merge($defaults, $this->state['database']);
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
     * Draw the step indicator.
     *
     * @return string
     */
    private function draw_steps_indicator(): string {
        $html = '<div class="steps">';

        foreach ($this->steps as $number => $title) {
            $active_class = ($number === $this->step) ? ' active' : '';
            $html .= '<div class="step' . $active_class . '">';
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

}
