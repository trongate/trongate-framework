<?php
/**
 * Database abstraction class for handling database operations using PDO.
 * Provides methods for querying, inserting, updating, deleting, and managing database records.
 * Supports connection management, parameter binding, batch operations, and table validation.
 * 
 * Security features include prepared statements and environment-aware error handling.
 */
class Db extends Trongate {

    private $host;
    private $port;
    private $user;
    private $pass;
    private $dbname;
    private $charset = 'utf8mb4';

    private $dbh;
    private $stmt;
    private $error;
    private $debug = false;
    private $query_caveat = 'The query shown above is how the query would look <i>before</i> binding.';
    private $is_dev_mode = false;

    /**
     * Initialize database connection
     * 
     * Establishes a PDO connection to the MySQL database using configuration from
     * config/database.php. Supports multiple database groups and provides environment-aware
     * error handling (detailed errors in development, generic errors in production).
     * 
     * IMPORTANT: Framework passes module_name as first parameter for proper integration
     * with the Trongate module system.
     * 
     * @param string|null $module_name The module name (passed by framework for integration)
     * @param string|null $db_group Database group name from config/database.php (defaults to 'default')
     * @return void
     * @throws Exception If database group is not configured
     * @throws Exception If database connection fails
     * 
     * Examples:
     * $db = new Db('users');                    // Framework instantiation with module name
     * $db = new Db('users', 'analytics');       // Framework instantiation with custom db group
     * $db = new Db();                           // Direct instantiation, uses 'default' group
     * 
     * Configuration example (config/database.php):
     * $databases['default'] = [
     *     'host' => 'localhost',
     *     'port' => '3306',
     *     'user' => 'root',
     *     'password' => 'secret',
     *     'database' => 'myapp'
     * ];
     */
    public function __construct(?string $module_name = null, ?string $db_group = null) {
        // Call parent constructor first - REQUIRED by framework!
        parent::__construct($module_name);
        
        // Determine environment mode
        $this->is_dev_mode = defined('ENV') && strtolower(ENV) === 'dev';
        
        // Default to 'default' group if none specified
        $db_group = $db_group ?? 'default';
        
        if (!isset($GLOBALS['databases'][$db_group])) {
            if ($this->is_dev_mode) {
                throw new Exception("Database group '{$db_group}' is not configured in /config/database.php");
            } else {
                throw new Exception("Configuration error.");
            }
        }
        
        $config = $GLOBALS['databases'][$db_group];
        
        $this->host = $config['host'];
        $this->port = $config['port'] ?? '3306';
        $this->user = $config['user'];
        $this->pass = $config['password'];
        $this->dbname = $config['database'];
        
        // If database name is empty, return without connecting
        if ($this->dbname === '') {
            return;
        }
        
        $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=' . $this->charset;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            
            if ($this->is_dev_mode) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                throw new Exception("Service unavailable.");
            }
        }
    }

    /**
     * Insert a single record
     * 
     * @param array $data Associative array of column => value
     * @param string $table Table name
     * @return int ID of newly inserted record
     * 
     * Example:
     * $id = $db->insert([
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com'
     * ], 'users');
     * 
     * @throws RuntimeException If table does not exist
     */
    public function insert(array $data, string $table): int {
        $this->validate_table_exists($table);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        if ($this->debug) {
            $this->show_query($sql, $data);
        }
        $this->prepare_and_execute($sql, $data);
        return (int) $this->dbh->lastInsertId();
    }

    /**
     * Update a record by ID
     * 
     * @param int $id Record ID
     * @param array $data Associative array of column => value
     * @param string $table Table name
     * @return bool Success
     * 
     * Example:
     * $db->update(15, ['name' => 'John Smith'], 'users');
     * 
     * @throws RuntimeException If table does not exist
     */
    public function update(int $id, array $data, string $table): bool {
        $this->validate_table_exists($table);
        $set_columns = [];
        foreach ($data as $key => $value) {
            $set_columns[] = "`$key` = :$key";
        }
        $set_clause = implode(', ', $set_columns);
        $data['id'] = $id;
        $sql = "UPDATE `$table` SET $set_clause WHERE `id` = :id";
        if ($this->debug) {
            $this->show_query($sql, $data);
        }
        return $this->prepare_and_execute($sql, $data);
    }

    /**
     * Delete a record by ID
     * 
     * @param int $id Record ID
     * @param string $table Table name
     * @return bool Success
     * 
     * Example:
     * $db->delete(15, 'users');
     * 
     * @throws RuntimeException If table does not exist
     */
    public function delete(int $id, string $table): bool {
        $this->validate_table_exists($table);
        $sql = "DELETE FROM `$table` WHERE id = :id";
        $data = ['id' => $id];
        if ($this->debug) {
            $this->show_query($sql, $data);
        }
        return $this->prepare_and_execute($sql, $data);
    }

    /**
     * Execute a raw SQL query
     * 
     * @param string $sql SQL query
     * @param string|null $return_type 'object', 'array', or null for no return
     * @return mixed Query results or null
     * 
     * Examples:
     * $results = $db->query('SELECT * FROM users WHERE age > 18', 'object');
     * $db->query('DELETE FROM sessions WHERE expires_at < NOW()');
     */
    public function query(string $sql, ?string $return_type = null): mixed {
        if ($this->debug) {
            $this->show_query($sql, []);
        }

        $this->prepare_and_execute($sql, []);

        if ($return_type === 'object') {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } elseif ($return_type === 'array') {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return null;
    }

    /**
     * Execute a raw SQL query with parameter binding
     * 
     * @param string $sql SQL query with named parameters
     * @param array $data Associative array of parameters
     * @param string|null $return_type 'object', 'array', or null for no return
     * @return mixed Query results or null
     * 
     * Example:
     * $results = $db->query_bind(
     *     'SELECT * FROM users WHERE age > :age AND status = :status',
     *     ['age' => 18, 'status' => 'active'],
     *     'object'
     * );
     */
    public function query_bind(string $sql, array $data, ?string $return_type = null): mixed {
        if ($this->debug) {
            $this->show_query($sql, $data);
        }

        $this->prepare_and_execute($sql, $data);

        if ($return_type === 'object') {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } elseif ($return_type === 'array') {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return null;
    }

    /**
     * Counts records in a table
     * 
     * @param string $table Table name
     * @return int Number of records
     * 
     * Example:
     * $total = $db->count('users');
     * 
     * @throws RuntimeException If table does not exist
     */
    public function count(string $table): int {
        $this->validate_table_exists($table);

        $sql = "SELECT COUNT(*) AS total FROM $table";

        if ($this->debug) {
            $this->show_query($sql, []);
        }

        $this->prepare_and_execute($sql, []);
        $result = $this->stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Insert multiple records into the specified table in a batch.
     *
     * This method inserts multiple records into the specified table using a batch insert
     * SQL statement. It's important to ensure that this method is not exposed to website
     * visitors to prevent potential security vulnerabilities.
     *
     * @param array $records An array containing associative arrays representing records to be inserted.
     * @param string $table The name of the table to insert records into.
     * @return int The number of records successfully inserted.
     * @throws Exception If an error occurs during the database operation.
     * 
     * Example:
     * $count = $db->insert_batch([
     *     ['name' => 'John Doe', 'email' => 'john@example.com'],
     *     ['name' => 'Jane Smith', 'email' => 'jane@example.com']
     * ], 'users');
     * 
     * @note This method should only be used in controlled environments and not exposed to untrusted users.
     */
    public function insert_batch(array $records, string $table): int {
        if (empty($records)) {
            return 0;
        }
        
        $this->validate_table_exists($table);
        
        // Retrieve field names from the first record
        $fields = array_keys($records[0]);
        
        // Generate placeholders for prepared statement
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        
        // Flatten the values array for execution
        $values = [];
        foreach ($records as $record) {
            $values = array_merge($values, array_values($record));
        }
        
        // Construct the SQL query
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES ';
        $sql .= implode(',', array_fill(0, count($records), "($placeholders)"));
        
        if ($this->debug) {
            $this->show_query($sql, $values);
        }
        
        // Prepare and execute the SQL statement
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($values);
        
        // Return the number of rows affected
        return $stmt->rowCount();
    }

    /**
     * Fetch records from a table with optional ordering
     * 
     * @param string $order_by Column name to order by
     * @param string $table Table name
     * @param string $order_direction Sort direction: 'ASC' or 'DESC' (default: 'ASC')
     * @param string|null $return_type 'object', 'array', or null (default: 'object')
     * @return array Array of records
     * 
     * Examples:
     * $users = $db->get('id', 'users');                    // ORDER BY id ASC
     * $users = $db->get('id', 'users', 'DESC');            // ORDER BY id DESC
     * $users = $db->get('name', 'users', 'ASC', 'array'); // Returns arrays instead of objects
     * 
     * @throws RuntimeException If table does not exist
     * @throws InvalidArgumentException If order direction is invalid
     */
    public function get(string $order_by, string $table, string $order_direction = 'ASC', ?string $return_type = 'object'): array {
        $this->validate_table_exists($table);
        
        // Validate order direction
        $order_direction = strtoupper($order_direction);
        if (!in_array($order_direction, ['ASC', 'DESC'])) {
            throw new InvalidArgumentException("Invalid order direction: '$order_direction'. Must be 'ASC' or 'DESC'.");
        }
        
        // Validate order_by column name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $order_by)) {
            throw new InvalidArgumentException("Invalid column name: '$order_by'");
        }
        
        $sql = "SELECT * FROM `$table` ORDER BY `$order_by` $order_direction";
        
        if ($this->debug) {
            $this->show_query($sql, []);
        }
        
        $this->prepare_and_execute($sql, []);
        
        if ($return_type === 'object') {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } elseif ($return_type === 'array') {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return [];
    }

    /**
     * Fetches a single record by its ID from a database table.
     *
     * @param int $id The ID of the record to fetch.
     * @param string $table The name of the database table to be queried.
     * @return object|false Returns an object representing the fetched record, 
     *                      or false if no record is found.
     * 
     * Example:
     * $user = $db->get_where(15, 'users');
     * 
     * @throws RuntimeException If the table doesn't exist
     */
    public function get_where(int $id, string $table): object|false {
        $this->validate_table_exists($table);
        
        $sql = "SELECT * FROM `$table` WHERE id = :id";
        $data = ['id' => $id];
        
        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
        }
        
        $this->prepare_and_execute($sql, $data);
        $item = $this->stmt->fetch(PDO::FETCH_OBJ);
        
        return $item !== false ? $item : false;
    }

    /**
     * Fetches a single record based on a column value from a database table.
     *
     * @param string $column The name of the column to filter by.
     * @param mixed $value The value to match against the specified column.
     * @param string $table The name of the database table to be queried.
     * @return object|false Returns an object representing the fetched record, 
     *                      or false if no record is found.
     * 
     * Examples:
     * $user = $db->get_one_where('email', 'john@example.com', 'users');
     * $product = $db->get_one_where('sku', 'ABC123', 'products');
     * 
     * @throws RuntimeException If the table doesn't exist
     * @throws InvalidArgumentException If column name contains invalid characters
     */
    public function get_one_where(string $column, mixed $value, string $table): object|false {
        $this->validate_table_exists($table);
        
        // Validate column name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Invalid column name: '$column'");
        }
        
        $sql = "SELECT * FROM `$table` WHERE `$column` = :value";
        $data = ['value' => $value];
        
        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
        }
        
        $this->prepare_and_execute($sql, $data);
        $item = $this->stmt->fetch(PDO::FETCH_OBJ);
        
        return $item !== false ? $item : false;
    }

    /**
     * Get records where column value is in array
     * 
     * Options array supports:
     * - 'columns' (string|array): Columns to select (default: '*')
     * - 'return_type' (string): 'object' or 'array' (default: 'object')
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $values Array of values to match
     * @param array $options Optional settings
     * @return array Array of records
     * 
     * Examples:
     * $users = $db->get_where_in('users', 'id', [1, 5, 10]);
     * $users = $db->get_where_in('users', 'status', ['active', 'pending']);
     * 
     * @throws RuntimeException If table does not exist
     */
    public function get_where_in(string $table, string $column, array $values, array $options = []): array {
        if (empty($values)) {
            return [];
        }

        $this->validate_table_exists($table);

        $columns = $options['columns'] ?? '*';
        $return_type = $options['return_type'] ?? 'object';

        $placeholders = [];
        $params = [];
        
        foreach ($values as $index => $value) {
            $param_name = "vin_{$index}";
            $placeholders[] = ":$param_name";
            $params[$param_name] = $value;
        }
        
        $placeholders_str = implode(',', $placeholders);
        $columns_str = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT $columns_str FROM $table WHERE $column IN ($placeholders_str)";

        if ($this->debug) {
            $this->show_query($sql, $params);
        }

        $this->prepare_and_execute($sql, $params);

        return ($return_type === 'object') 
            ? $this->stmt->fetchAll(PDO::FETCH_OBJ) 
            : $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a table exists
     * 
     * @param string $table Table name
     * @return bool True if exists
     * 
     * Example:
     * if ($db->table_exists('users')) { ... }
     */
    public function table_exists(string $table): bool {
        try {
            $sql = "SHOW TABLES LIKE :table";
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':table', $table, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_NUM) !== false;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get all table names
     * 
     * @return array Array of table names
     * 
     * Example:
     * $tables = $db->get_tables();
     */
    public function get_tables(): array {
        try {
            $sql = "SHOW TABLES";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return [];
        }
    }

    /**
     * Get table structure details
     * 
     * @param string $table Table name
     * @param bool $names_only Return only column names
     * @return array|false Column details or names
     * 
     * Examples:
     * $columns = $db->describe_table('users');
     * $names = $db->describe_table('users', true);
     */
    public function describe_table(string $table, bool $names_only = false): array|false {
        try {
            $sql = 'DESCRIBE ' . $table;
            $columns = $this->query($sql, 'array');

            if ($names_only) {
                return array_column($columns, 'Field');
            }

            return $columns;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Validate that a table exists
     * 
     * @throws RuntimeException If table does not exist
     */
    private function validate_table_exists(string $table): void {
        if (!$this->table_exists($table)) {
            if ($this->is_dev_mode) {
                // Development: Detailed error
                throw new RuntimeException(
                    "Table '$table' does not exist in database '{$this->dbname}'. " .
                    "Available tables: " . implode(', ', $this->get_tables())
                );
            } else {
                // Production: Completely generic error
                throw new RuntimeException("Invalid operation.");
            }
        }
    }

    /**
     * Prepare and execute SQL statement with parameter binding
     */
    private function prepare_and_execute(string $sql, array $data = []): bool {
        $this->stmt = $this->dbh->prepare($sql);

        foreach ($data as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            
            $param_key = (str_starts_with($key, ':')) ? $key : ":$key";
            $this->stmt->bindValue($param_key, $value, $type);
        }

        return $this->stmt->execute();
    }

    /**
     * Display query for debugging
     */
    private function show_query(string $query, array $data, ?string $caveat = null): void {
        $keys = array();
        $values = $data;

        foreach ($data as $key => $value) {
            $keys[] = '/:' . ltrim($key, ':') . '/';

            if (is_string($value)) {
                $values[$key] = "'" . $value . "'";
            } elseif (is_array($value)) {
                $values[$key] = "'" . implode("','", $value) . "'";
            } elseif (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        $query = preg_replace($keys, $values, $query);

        $caveat_info = '';
        if (isset($caveat)) {
            $caveat_info = '<br><hr><div style="font-size: 0.8em;"><b>PLEASE NOTE:</b> ' . $caveat;
            $caveat_info .= ' PDO currently has no means of displaying previous query executed.</div>';
        }

        echo '<div class="tg-rprt"><b>QUERY TO BE EXECUTED:</b><br><br>  -> ';
        echo $query . $caveat_info . '</div>';
    ?>

    <style>
        .tg-rprt {
            color: #383623;
            background-color: #efe79e;
            font-family: "Lucida Console", Monaco, monospace;
            padding: 1em;
            border: 1px #383623 solid;
            clear: both !important;
            margin: 1em 0;
        }
    </style>

    <?php
    }
}