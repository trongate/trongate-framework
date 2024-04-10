<?php

/**
 * Class Model
 * Handles database interactions through PDO, providing a structured approach for querying and managing database data.
 */
class Model {

    private $host = HOST;
    private $port = '';
    private $user = USER;
    private $pass = PASSWORD;
    private $dbname = DATABASE;

    private $dbh;
    private $stmt;
    private $error;
    private $debug = false;
    private $query_caveat = 'The query shown above is how the query would look <i>before</i> binding.';
    private $current_module;

    /**
     * Constructor for the Model class.
     * 
     * @param string|null $current_module The current module. Default is null.
     * @return void
     */
    public function __construct(?string $current_module = null): void {

        if (DATABASE == '') {
            return;
        }

        $this->port = (defined('PORT') ? PORT : '3306');
        $this->current_module = $current_module;

        $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            echo $this->error;
            die();
        }
    }

    /**
     * Determines the PDO parameter type for a given value.
     * 
     * @param mixed $value The value to determine the parameter type for.
     * @return int The PDO parameter type.
     */
    private function get_param_type($value): int {

        switch (true) {
            case is_int($value):
                $type = PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = PDO::PARAM_NULL;
                break;
            default:
                $type = PDO::PARAM_STR;
        }

        return $type;
    }

    /**
     * Prepares and executes a SQL statement with data binding.
     * 
     * @param string $sql The SQL statement to execute.
     * @param array $data The data to bind to the SQL statement.
     * @return bool Returns true on success or false on failure.
     */
    function prepare_and_execute(string $sql, array $data): bool {

        $this->stmt = $this->dbh->prepare($sql);

        if (isset($data[0])) { //unnamaed data
            return $this->stmt->execute($data);
        } else {

            foreach ($data as $key => $value) {
                $type = $this->get_param_type($value);
                $this->stmt->bindValue(":$key", $value, $type);
            }

            return $this->stmt->execute();
        }
    }

    /**
     * Retrieves the table name from the URL or uses the current module.
     * 
     * This method first checks if the current module is set. If it is, it returns the module name
     * as the table name. Otherwise, it extracts the table name from the first segment of the URL.
     * 
     * @return string The table name.
     */
    private function get_table_from_url(): string {
        // Use $this->current_module if set, otherwise, use the first URL segment
        return isset($this->current_module) ? $this->current_module : segment(1);
    }

    /**
     * Adds limit and offset to the SQL query if both are numeric.
     * 
     * @param string $sql The SQL query.
     * @param int|null $limit The limit value.
     * @param int|null $offset The offset value.
     * @return string The modified SQL query.
     */
    private function add_limit_offset(string $sql, ?int $limit, ?int $offset): string {

        if ((is_numeric($limit)) && (is_numeric($offset))) {
            $limit_results = true;
            $sql .= " LIMIT $offset, $limit";
        }

        return $sql;
    }

    /**
     * Retrieves all tables in the database.
     * 
     * @return array The array of table names.
     */
    public function get_all_tables(): array {
        
        $tables = [];
        $sql = 'show tables';
        $column_name = 'Tables_in_' . DATABASE;
        $rows = $this->query($sql, 'array');
        foreach ($rows as $row) {
            $tables[] = $row[$column_name];
        }

        return $tables;
    }

    /**
     * Retrieves records from a table.
     * 
     * @param string|null $order_by The column to order by. Default is null.
     * @param string|null $target_tbl The target table name. Default is null.
     * @param int|null $limit The limit value. Default is null.
     * @param int|null $offset The offset value. Default is null.
     * @return array The array of fetched records.
     */
    public function get(?string $order_by = null, ?string $target_tbl = null, ?int $limit = null, ?int $offset = null): array {

        $order_by = (!isset($order_by)) ? 'id' : $order_by;

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_tbl order by $order_by";

        if ((isset($limit)) && (isset($offset))) {
            settype($limit, 'int');
            settype($offset, 'int');
            $sql = $this->add_limit_offset($sql, $limit, $offset);
        }

        if ($this->debug == true) {
            $data = [];
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();
        $query = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $query;
    }

    /**
     * Retrieves records from a table based on custom conditions.
     * 
     * @param string $column The column to filter by.
     * @param mixed $value The value to filter.
     * @param string $operator The comparison operator. Default is '='.
     * @param string|null $order_by The column to order by. Default is 'id'.
     * @param string|null $target_tbl The target table name. Default is null.
     * @param int|null $limit The limit value. Default is null.
     * @param int|null $offset The offset value. Default is null.
     * @return array|null The array of fetched records, or null if the query fails.
     */
    public function get_where_custom(string $column, $value, string $operator = '=', string $order_by = 'id', ?string $target_tbl = null, ?int $limit = null, ?int $offset = null): ?array {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $data[$column] = $value;
        $sql = "SELECT * FROM $target_tbl where $column $operator :$column order by $order_by";

        if ((isset($limit))) {

            if (!isset($offset)) {
                $offset = 0;
            }

            $sql = $this->add_limit_offset($sql, $limit, $offset);
        }

        if ($this->debug == true) {

            $operator = strtoupper($operator);
            if (($operator == 'LIKE') || ($operator == 'NOT LIKE')) {
                $value = '%' . $value . '%';
                $data[$column] = $value;
            }

            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $items = $this->stmt->fetchAll(PDO::FETCH_OBJ);
            return $items;
        }
    }

    /**
     * Fetches a single record from a table based on the provided ID.
     * 
     * @param int $id The ID of the record to fetch.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return object|null The fetched record as an object, or null if the query fails.
     */
    public function get_where(int $id, ?string $target_tbl = null): ?object {

        $data['id'] = (int) $id;

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_tbl where id = :id";

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $item = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $item;
        }
    }

    /**
     * Fetches a single record from a table based on the provided column and value.
     * 
     * @param string $column The column to filter by.
     * @param mixed $value The value to filter.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return object|null The fetched record as an object, or null if the query fails.
     */
    public function get_one_where(string $column, $value, ?string $target_tbl = null): ?object {

        $data[$column] = $value;

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_tbl where $column = :$column";

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $item = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $item;
        }
    }

    /**
     * Fetches multiple records from a table based on the provided column and value.
     * 
     * @param string $column The column to filter by.
     * @param mixed $value The value to filter.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return array|null The array of fetched records, or null if the query fails.
     */
    public function get_many_where(string $column, $value, ?string $target_tbl = null): ?array {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $data[$column] = $value;
        $sql = 'select * from ' . $target_tbl . ' where ' . $column . ' = :' . $column;

        $query = $this->query_bind($sql, $data, 'object');

        return $query;
    }

    /**
     * Counts the number of rows in a table.
     * 
     * @param string|null $target_tbl The target table name. Default is null.
     * @return int|null The number of rows in the table, or null if the query fails.
     */
    public function count(?string $target_tbl = null): ?int {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT COUNT(id) as total FROM $target_tbl";
        $data = [];

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $obj = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $obj->total;
        }
    }

    /**
     * Counts the number of rows in a table based on custom conditions.
     * 
     * @param string $column The column to filter by.
     * @param mixed $value The value to filter.
     * @param string $operator The comparison operator. Default is '='.
     * @param string|null $order_by The column to order by. Default is 'id'.
     * @param string|null $target_tbl The target table name. Default is null.
     * @param int|null $limit The limit value. Default is null.
     * @param int|null $offset The offset value. Default is null.
     * @return int The number of rows in the table.
     */
    public function count_where(string $column, $value, string $operator = '=', string $order_by = 'id', ?string $target_tbl = null, ?int $limit = null, ?int $offset = null): int {

        $query = $this->get_where_custom($column, $value, $operator, $order_by, $target_tbl, $limit, $offset);
        $num_rows = count($query);
        return $num_rows;
    }

    /**
     * Counts the number of rows in a table based on a single condition.
     * 
     * @param string $column The column to filter by.
     * @param mixed $value The value to filter.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return int|null The number of rows in the table, or null if the query fails.
     */
    public function count_rows(string $column, $value, ?string $target_tbl = null): ?int {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $data[$column] = $value;
        $sql = 'SELECT COUNT(id) as total from ' . $target_tbl . ' where ' . $column . ' = :' . $column;

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $obj = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $obj->total;
        }
    }

    /**
     * Retrieves the maximum value of the 'id' column from a table.
     * 
     * @param string|null $target_tbl The target table name. Default is null.
     * @return int|null The maximum value of the 'id' column, or null if the query fails.
     */
    public function get_max(?string $target_tbl = null): ?int {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT MAX(id) AS max_id FROM $target_tbl";
        $data = [];

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result == true) {
            $assoc = $this->stmt->fetch(PDO::FETCH_ASSOC);
            $max_id = $assoc['max_id'];
            return $max_id;
        }
    }

    /**
     * Shows the formatted query to be executed.
     * 
     * @param string $query The SQL query to be executed.
     * @param array $data An array containing the query parameters.
     * @param string|null $caveat Additional information or notes about the query. Default is null.
     * @return void
     */
    public function show_query(string $query, array $data, ?string $caveat = null): void {

        $keys = array();
        $values = $data;
        $named_params = true;

        # build a regular expression for each parameter
        foreach ($data as $key => $value) {

            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
                $named_params = false;
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        if ($named_params == true) {
            $query = preg_replace($keys, $values, $query);
        } else {

            $query = $query . ' ';
            $bits = explode(' ? ', $query);

            $query = '';
            for ($i = 0; $i < count($bits); $i++) {
                $query .= $bits[$i];

                if (isset($values[$i])) {
                    $query .= ' ' . $values[$i] . ' ';
                }
            }
        }

        if (!isset($caveat)) {
            $caveat_info = '';
        } else {

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

    /**
     * Inserts data into the specified table.
     * 
     * @param array $data An associative array containing the data to be inserted.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return int The ID of the newly inserted row.
     */
    public function insert(array $data, ?string $target_tbl = null): int {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = 'INSERT INTO `' . $target_tbl . '` (';
        $sql .= '`' . implode("`, `", array_keys($data)) . '`)';
        $sql .= ' VALUES (';

        foreach ($data as $key => $value) {
            $sql .= ':' . $key . ', ';
        }

        $sql = rtrim($sql, ', ');
        $sql .= ')';

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $this->prepare_and_execute($sql, $data);
        $id = $this->dbh->lastInsertId();
        return $id;
    }

    /**
     * Updates data in the specified table based on the provided ID.
     * 
     * @param int $update_id The ID of the row to be updated.
     * @param array $data An associative array containing the data to be updated.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return void
     */
    public function update(int $update_id, array $data, ?string $target_tbl = null): void {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "UPDATE `$target_tbl` SET ";

        foreach ($data as $key => $value) {
            $sql .= "`$key` = :$key, ";
        }

        $sql = rtrim($sql, ', ');
        $sql .= " WHERE `$target_tbl`.`id` = :id";

        $data['id'] = (int) $update_id;
        $data = $data;

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $this->prepare_and_execute($sql, $data);
    }

    /**
     * Updates data in the specified table based on the provided column and its value.
     * 
     * @param string $column The column name to filter the update operation.
     * @param mixed $column_value The value of the column to filter the update operation.
     * @param array $data An associative array containing the data to be updated.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return void
     */
    public function update_where(string $column, $column_value, array $data, ?string $target_tbl = null): void {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "UPDATE `$target_tbl` SET ";

        foreach ($data as $key => $value) {
            $sql .= "`$key` = :$key, ";
        }

        $sql = rtrim($sql, ', ');
        $sql .= " WHERE `$target_tbl`.`$column` = :value";

        $data['value'] = $column_value;
        $data = $data;

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $this->prepare_and_execute($sql, $data);
    }

    /**
     * Deletes a record from the specified table based on the provided ID.
     * 
     * @param int $id The ID of the record to be deleted.
     * @param string|null $target_tbl The target table name. Default is null.
     * @return void
     */
    public function delete(int $id, ?string $target_tbl = null): void {

        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "DELETE from `$target_tbl` WHERE id = :id ";
        $data['id'] = (int) $id;

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $this->prepare_and_execute($sql, $data);
    }

    /**
     * Executes a SQL query with an optional return type.
     * 
     * WARNING: Use with caution due to the risk of SQL injection.
     * 
     * @param string $sql The SQL query to execute.
     * @param string|bool $return_type (Optional) The return type. Can be 'object', 'array', or false (default).
     * @return array|object|null Returns the query result if a return type is specified, null otherwise.
     */
    public function query(string $sql, $return_type = false): ?array|object {

        $data = [];

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data);
        }

        $this->prepare_and_execute($sql, $data);

        if (($return_type == 'object') || ($return_type == 'array')) {

            if ($return_type == 'object') {
                $query = $this->stmt->fetchAll(PDO::FETCH_OBJ);
            } else {
                $query = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $query;
        }
    }

    /**
     * Executes a prepared SQL query with bound parameters and an optional return type.
     * 
     * @param string $sql The SQL query to execute.
     * @param array $data An associative array of parameter values to bind to the query.
     * @param string|bool $return_type (Optional) The return type. Can be 'object', 'array', or false (default).
     * @return array|object|null Returns the query result if a return type is specified, null otherwise.
     */
    public function query_bind(string $sql, array $data, $return_type = false): ?array|object {

        if ($this->debug == true) {
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        $this->prepare_and_execute($sql, $data);

        if (($return_type == 'object') || ($return_type == 'array')) {

            if ($return_type == 'object') {
                $query = $this->stmt->fetchAll(PDO::FETCH_OBJ);
            } else {
                $query = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $query;
        }
    }

    /**
     * Attempts to truncate a table if it contains no rows.
     * 
     * @param string $tablename The name of the table to truncate.
     * @return void
     */
    public function attempt_truncate(string $tablename): void {

        $num_rows = $this->count($tablename);

        if ($num_rows == 0) {
            $sql = 'TRUNCATE ' . $tablename;
            $this->query($sql);
        }
    }

    /**
     * Inserts multiple records into a table in a batch operation.
     * 
     * WARNING: Never let your website visitors invoke this method!
     * 
     * @param string $table The name of the table to insert records into.
     * @param array[] $records An array containing the records to be inserted. Each record should be an associative array.
     * @return int The number of records inserted.
     */
    public function insert_batch(string $table, array $records): int {

        //WARNING:  Never let your website visitors invoke this method!
        $fields = array_keys($records[0]);
        $placeHolders = substr(str_repeat(',?', count($fields)), 1);
        $values = [];
        foreach ($records as $record) {
            array_push($values, ...array_values($record));
        }

        $sql = 'INSERT INTO ' . $table . ' (';
        $sql .= implode(',', $fields);
        $sql .= ') VALUES (';
        $sql .= implode('),(', array_fill(0, count($records), $placeHolders));
        $sql .= ')';

        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($values);

        $count = $stmt->rowCount();
        return $count;
    }

    /**
     * Executes a SQL statement if the environment is in development mode.
     * 
     * If the environment is not in 'dev' mode, a message indicating the feature is disabled is echoed.
     * 
     * @param string $sql The SQL statement to execute.
     * @return void
     */
    public function exec(string $sql): void {

        if (ENV == 'dev') {
            //this gets used on auto module table setups
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute();
        } else {
            echo 'Feature disabled, since not on \'dev\' mode.';
        }
    }
}
