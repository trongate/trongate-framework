<?php
class Setup_model extends Model {

    /**
     * Get database configuration form data.
     *
     * Returns default values, overridden by POST data if the form has been submitted.
     *
     * @return array<string, string>
     */
    public function get_db_config_form_data(): array {

        $data = [
            'host'          => '127.0.0.1',
            'port'          => '3306',
            'user'          => 'root',
            'password'      => '',
            'database'      => $this->suggest_db_name(),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($data, [
                'host'          => (string) post('host'),
                'port'          => (string) post('port'),
                'user'          => (string) post('user'),
                'password'      => (string) post('password'),
                'database'      => (string) post('database'),
            ]);
        }

        $data['form_location'] = 'setup/submit_database_config';

        return $data;
    }

    /**
     * Suggest a database name based on the BASE_URL path segment.
     *
     * @return string
     */
    private function suggest_db_name(): string {

        $url_path = parse_url(BASE_URL, PHP_URL_PATH);

        if (!$url_path || $url_path === '/') {
            return 'trongate_app';
        }

        $parts = explode('/', trim($url_path, '/'));
        $last  = end($parts);

        return ($last !== false && $last !== '')
            ? strtolower($last)
            : 'trongate_app';
    }

    public function get_db_config_path() {
        $config_path = APPPATH.'config/database.php';
        return $config_path;
    }

    public function build_config_code($db_config_donor) {
        $host = $_SESSION['host'] ?? '';
        $port = $_SESSION['port'] ?? '';
        $user = $_SESSION['user'] ?? '';
        $password = $_SESSION['password'] ?? '';
        $database = $_SESSION['database'] ?? '';

        $db_config_donor = str_replace('<host>', $host, $db_config_donor);
        $db_config_donor = str_replace('<port>', $port, $db_config_donor);
        $db_config_donor = str_replace('<user>', $user, $db_config_donor);
        $db_config_donor = str_replace('<password>', $password, $db_config_donor);
        $db_config_donor = str_replace('<database>', $database, $db_config_donor);
        $db_config_donor = str_replace('&lt;', '<', $db_config_donor);
        return $db_config_donor;
    }

    /**
     * Verify the database configuration by attempting a live connection.
     *
     * Runs SHOW TABLES as a health check via a raw PDO connection built
     * from the credentials already in config/database.php.
     *
     * @return bool|string TRUE on success, error message string on failure.
     */
    public function verify_db_connection(): bool|string {

        try {
            $pdo = $this->get_pdo_connection();
            $stmt = $pdo->prepare('SHOW TABLES');
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return 'Database connection failed: ' . $e->getMessage();
        }
    }

    /**
     * Run SQL and create admin user.
     *
     * Runs the setup SQL to create framework tables and inserts an admin
     * user with the provided credentials.
     *
     * @param  array $data  Associative array with keys 'username', 'email', 'password'.
     * @return array        Results array with keys: db_setup, admin_created,
     *                      username, email, db_error.
     */
    public function create_admin_account(array $data): array {

        $results = [
            'db_setup'      => false,
            'admin_created' => false,
            'username'      => $data['username'],
            'email'         => $data['email'],
            'db_error'      => '',
        ];

        try {

            $pdo = $this->get_pdo_connection();

            $sql = file_get_contents(APPPATH . 'modules/setup/sql/setup.sql');
            $pdo->exec($sql);

            // Truncate so the wizard can safely be re-run
            $pdo->exec('TRUNCATE TABLE trongate_administrators');
            $pdo->exec('TRUNCATE TABLE trongate_users');

            // Create the trongate_users entry
            $stmt = $pdo->prepare(
                'INSERT INTO trongate_users (code, user_level_id) VALUES (:code, :level)'
            );
            $stmt->execute([':code' => make_rand_str(32), ':level' => 1]);
            $uid = $pdo->lastInsertId();

            // Create the admin record
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 11]);
            $stmt = $pdo->prepare(
                'INSERT INTO trongate_administrators '
                . '(trongate_user_id, username, email, password, active) '
                . 'VALUES (:uid, :username, :email, :password, 1)'
            );
            $stmt->execute([
                ':uid'      => $uid,
                ':username' => $data['username'],
                ':email'    => $data['email'],
                ':password' => $hash,
            ]);

            $results['db_setup']      = true;
            $results['admin_created'] = true;

        } catch (\Exception $e) {
            $results['db_error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Update DEFAULT_MODULE in config/config.php to 'welcome'.
     *
     * @return bool|string TRUE on success, error message on failure.
     */
    public function update_default_mod(): bool|string {

        $config_path = APPPATH . 'config/config.php';

        if (!file_exists($config_path)) {
            return 'config.php not found.';
        }

        $main = @file_get_contents($config_path);

        if ($main === false) {
            return 'Could not read config.php.';
        }

        // Verify that DEFAULT_MODULE is already set to 'welcome'
        if (preg_match("/define\('DEFAULT_MODULE',\s*'welcome'\)/", $main)) {
            return true;
        }

        return 'DEFAULT_MODULE has not been set to the correct value.';
    }

    /**
     * Build a raw PDO connection using the credentials that are already
     * in config/database.php (via $GLOBALS['databases']).
     *
     * @return \PDO
     *
     * @throws \PDOException If the connection fails.
     */
    private function get_pdo_connection(): \PDO {

        $config = $GLOBALS['databases']['default'];

        $dsn = 'mysql:host=' . $config['host']
             . ';port=' . ($config['port'] ?? '3306')
             . ';dbname=' . $config['database'];

        $pdo = new \PDO($dsn, $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        return $pdo;
    }

}