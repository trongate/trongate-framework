<?php
class Setup extends Trongate {

    public function index() {
        if (BASE_URL === '****') {
            http_response_code(503);
            echo '<code>BASE_URL</code> has not been set.';
            die();
        }

        $this->view('setup');
    }

    public function database_config() {
        $data = $this->model->get_db_config_form_data();
        $this->view('database_config', $data);
    }

    public function submit_database_config() {
        $this->validation->set_rules('host', 'host', 'required|min_length[3]|max_length[255]');
        $this->validation->set_rules('port', 'port', 'integer|greater_than[0]|less_than[65536]');
        $this->validation->set_rules('user', 'username', 'required|min_length[1]|max_length[64]');
        $this->validation->set_rules('password', 'password', 'max_length[255]');
        $this->validation->set_rules('database', 'database name', 'required|min_length[1]|max_length[64]|alpha_numeric_underscores|callback_test_db_connection');

        if ($this->validation->run() === true) {

            $this->build_session_data();

            $this->database_config_setup();

        } else {

            echo validation_errors(422);
        }
    }

    public function build_session_data() {
        $_SESSION['host'] = post('host');
        $_SESSION['port'] = post('port');
        $_SESSION['user'] = post('user');
        $_SESSION['password'] = post('password');
        $_SESSION['database'] = post('database');
    }

    public function database_config_setup() {
        $data = [];
        $data['db_config_path'] = $this->model->get_db_config_path();

        $db_config_donor = $this->view('db_config_donor', [], true);
        $data['db_config_code'] = $this->model->build_config_code($db_config_donor);
        $this->view('database_config_setup', $data);
    }

    public function submit_database_config_setup() {
        $this->validation->set_rules('verification_trigger', 'verification', 'callback_db_config_check');
        $result = $this->validation->run();

        if ($result === true) {
            $this->admin_account();
        } else {
            echo validation_errors(422);
        }

    }

    /**
     * Validation callback: confirm that database.php is working by
     * attempting a live connection.
     *
     * Delegates the actual connection attempt to Setup_model.
     *
     * @return bool|string TRUE on success, error message string on failure.
     */
    public function db_config_check(mixed $verification_trigger = null): bool|string {

        block_url('setup/db_config_check');

        return $this->model->verify_db_connection();
    }

    /**
     * Render the admin account creation form (Step 3).
     *
     * @return void
     */
    public function admin_account(): void {
        $data = [
            'username' => post('username', true) ?: '',
            'email'    => post('email', true) ?: '',
        ];

        $this->view('admin_account', $data);
    }

    /**
     * Validate and submit admin account creation.
     *
     * On success, runs the SQL and creates the admin user. Then advances
     * to Stage A (update DEFAULT_MODULE) rather than going straight to
     * the congratulations page.
     *
     * @return void
     */
    public function submit_admin_account(): void {

        $this->validation->set_rules('username', 'username', 'required|min_length[2]');
        $this->validation->set_rules('email', 'email address', 'required|valid_email');
        $this->validation->set_rules('password', 'password', 'required|min_length[8]');
        $this->validation->set_rules('confirm_password', 'confirm password', 'required|matches[password]');

        if ($this->validation->run() === true) {

            $data = [
                'username' => post('username', true),
                'email'    => post('email', true),
                'password' => post('password'),
            ];

            $result = $this->model->create_admin_account($data);

            if ($result['admin_created'] === true) {
                $_SESSION['setup_admin_created'] = true;
                $_SESSION['setup_admin_username'] = $data['username'];
                $this->update_default_mod();
            } else {
                // Admin creation failed — show the form again with an error
                echo validation_errors(422);
            }

        } else {
            echo validation_errors(422);
        }
    }

    /**
     * Render the update default module form (Stage A).
     *
     * @return void
     */
    public function update_default_mod(): void {
        $data = [
            'current_default_module' => DEFAULT_MODULE,
            'config_path' => APPPATH . 'config/config.php',
            'replacement_code' => "define('DEFAULT_MODULE', 'welcome');",
        ];

        $this->view('update_default_module', $data);
    }

    /**
     * Submit update of DEFAULT_MODULE in config.php.
     *
     * On success, advances to Stage B (delete setup module).
     *
     * @return void
     */
    public function submit_update_default_mod(): void {

        $this->validation->set_rules('current_default_module', 'current default module', 'callback_update_default_mod_check');

        if ($this->validation->run() === true) {
            $result = [
                'db_setup'       => true,
                'admin_created'  => true,
                'config_updated' => true,
                'username'       => $_SESSION['setup_admin_username'] ?? '',
                'email'          => '',
                'db_error'       => '',
            ];
            $this->view('setup_complete', $result);
        } else {
            echo validation_errors(422);
        }
    }

    /**
     * Validation callback: update DEFAULT_MODULE to 'welcome' in config.php.
     *
     * Reads the current config.php, replaces the DEFAULT_MODULE definition,
     * and writes it back. Returns TRUE on success, error message on failure.
     *
     * @param  string $current_default_module The current value (ignored; read from file).
     * @return bool|string
     */
    public function update_default_mod_check(mixed $current_default_module = null): bool|string {

        block_url('setup/update_default_mod_check');

        return $this->model->update_default_mod();
    }


    /**
     * Validation callback to test a MySQL database connection.
     *
     * Attempts to connect to the MySQL server using the provided POST
     * credentials. If the target database does not exist, an attempt is made
     * to create it. A final connection is then attempted against the specified
     * database to confirm usability.
     *
     * This method is intended for use as a Trongate validation callback
     * (e.g. 'callback_test_db_connection').
     *
     * @return bool|string Returns TRUE on success, or an error message string on failure.
     */
    public function test_db_connection(): bool|string {

        $host     = post('host');
        $port     = post('port') !== '' ? post('port') : '3306';
        $user     = post('user');
        $password = post('password');
        $database = post('database');

        try {

            // Step 1: connect to MySQL server (not specific DB yet)
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            // Step 2: check if DB exists
            $stmt = $pdo->prepare("
                SELECT SCHEMA_NAME 
                FROM INFORMATION_SCHEMA.SCHEMATA 
                WHERE SCHEMA_NAME = :database
            ");
            $stmt->execute(['database' => $database]);

            $exists = $stmt->fetch();

            if (!$exists) {

                // Step 3: attempt to create DB
                $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            // Optional: final sanity check (connect directly to DB)
            $dsn_db = "mysql:host={$host};port={$port};dbname={$database}";
            new PDO($dsn_db, $user, $password);

        } catch (PDOException $e) {
            return 'Unable to connect to database';
        }

        return true;
    }

    /**
     * Draw the step indicator HTML for the setup wizard.
     *
     * @param int $current_step The current active step (1-5).
     * @return string The rendered steps indicator HTML.
     */
    public function draw_steps_indicator(int $current_step): string {
        $steps = [
            1 => 'Welcome',
            2 => 'Database',
            3 => 'Admin',
            4 => 'Finalize',
            5 => 'Finish',
        ];

        $html = '<div class="steps">';

        foreach ($steps as $num => $title) {
            $active_class = ($num === $current_step) ? ' active' : '';
            $html .= '<div class="step' . $active_class . '">';
            $html .= '<span class="step-number">' . $num . '</span>';
            $html .= '<span class="step-title">' . $title . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a help link paragraph for the setup wizard.
     *
     * Displays "Having difficulties? Need help? CLICK HERE" where HERE
     * is a clickable link that loads Dave's help message into .setup-container.
     *
     * @return string The help link HTML.
     */
    public function draw_help_link(): string {
        $html = '<p class="help-link">';
        $html .= 'Having difficulties? Need help? ';
        $html .= '<a href="#" mx-get="setup/show_help_message" mx-target=".setup-container" mx-indicator=".spinner" mx-target-loading="cloak" mx-after-swap="setupEmailDisplay">CLICK HERE</a>';
        $html .= '</p>';

        return $html;
    }

    /**
     * Show the help message from Dave Connelly inside .setup-container.
     *
     * Called via MX when the user clicks the help link.
     *
     * @return void
     */
    public function show_help_message(): void {
        $this->view('help_message');
    }

}