<?php

/**
 * Provides database interaction functionalities including
 * fetching, inserting, updating, and resequencing records. This class is 
 * also used by Trongate's Module Import Wizard for executing SQL statements.
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
     * @param string|null $current_module (optional) The current module name. Default is null.
     */
    public function __construct(?string $current_module = null) {

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
     * Retrieves rows from a database table based on optional parameters.
     *
     * @param string|null $order_by (optional) The column to order results by. Default is 'id'.
     * @param string|null $target_tbl (optional) The name of the database table to query. Default is null.
     * @param int|null $limit (optional) The maximum number of results to return. Default is null.
     * @param int $offset (optional) The number of rows to skip before fetching results. Default is 0.
     * @return array Returns an array of objects representing the fetched rows.
     */
    public function get(?string $order_by = null, ?string $target_tbl = null, ?int $limit = null, int $offset = 0): array {
        // Set default order_by if not provided
        $order_by = $order_by ?? 'id';

        // Determine the target table if not provided
        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        // Build the base SQL query
        $sql = "SELECT * FROM $target_tbl ORDER BY $order_by";

        // Add LIMIT and OFFSET if provided
        if (!is_null($limit)) {
            settype($limit, 'int');
            settype($offset, 'int');
            $sql = $this->add_limit_offset($sql, $limit, $offset);
        }

        // Debugging: show query if debug mode is enabled
        if ($this->debug == true) {
            $data = [];
            $query_to_execute = $this->show_query($sql, $data, $this->query_caveat);
        }

        // Prepare and execute the query
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();

        // Fetch and return the results
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $rows;
    }

    /**
     * Retrieves rows from a database table based on custom conditions.
     *
     * @param string $column The name of the table column referred to when fetching results.
     * @param mixed $value The value that should be matched against the target table column.
     * @param string $operator (optional) The comparison operator. Default is '='.
     * @param string $order_by (optional) The column to order results by. Default is 'id'.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @param int|null $limit (optional) The maximum number of results to return. Default is null.
     * @param int|null $offset (optional) The number of rows to skip before fetching results. Default is null.
     * @return array Returns an array of objects representing the fetched rows. If no records are found, an empty array is returned.
     * @throws InvalidArgumentException If an invalid operator is provided.
     * @throws RuntimeException If the query execution fails.
     */
    public function get_where_custom(string $column, $value, string $operator = '=', string $order_by = 'id', ?string $target_table = null, ?int $limit = null, ?int $offset = null): array {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Validate operator
        $valid_operators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE'];
        if (!in_array($operator, $valid_operators)) {
            throw new InvalidArgumentException("Invalid operator: $operator");
        }

        // Build the SQL query
        $sql = "SELECT * FROM $target_table WHERE $column $operator :$column ORDER BY $order_by";

        // Set default values for limit and offset
        $limit = $limit ?? PHP_INT_MAX;
        $offset = $offset ?? 0;

        // Add LIMIT and OFFSET if provided
        if ($limit !== PHP_INT_MAX) {
            $sql = $this->add_limit_offset($sql, $limit, $offset);
        }

        // Debugging
        if ($this->debug) {
            // Adjust value for LIKE operator
            if (in_array($operator, ['LIKE', 'NOT LIKE'])) {
                $value = '%' . $value . '%';
            }

            $data[$column] = $value;
            $this->show_query($sql, $data, $this->query_caveat);
        }

        // Execute the query
        $result = $this->prepare_and_execute($sql, [$column => $value]);

        // Handle query execution result
        if ($result) {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Fetches a single record by its ID from a database table.
     *
     * @param int $id The ID of the record to fetch.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @return object|false Returns an object representing the fetched record, or false if no record is found.
     * @throws RuntimeException If the query execution fails.
     */
    public function get_where(int $id, ?string $target_table = null): object|false {
        $data['id'] = $id;

        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_table WHERE id = :id";

        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result) {
            $item = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $item !== false ? $item : false;
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Fetches a single record based on a column value from a database table.
     *
     * @param string $column The name of the column to filter by.
     * @param mixed $value The value to match against the specified column.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @return object|false Returns an object representing the fetched record, or false if no record is found.
     * @throws RuntimeException If the query execution fails.
     */
    public function get_one_where(string $column, $value, ?string $target_table = null): object|false {
        $data[$column] = $value;

        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_table WHERE $column = :$column";

        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
        }

        $result = $this->prepare_and_execute($sql, $data);

        if ($result) {
            $item = $this->stmt->fetch(PDO::FETCH_OBJ);
            return $item !== false ? $item : false;
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Retrieves multiple records from a database table based on custom conditions.
     *
     * @param string $column The name of the table column referred to when fetching results.
     * @param mixed $value The value that should be matched against the target table column.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @return array Returns an array of objects representing the fetched rows. If no records are found, an empty array is returned.
     * @throws RuntimeException If the query execution fails.
     */
    public function get_many_where(string $column, $value, ?string $target_table = null): array {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        $sql = "SELECT * FROM $target_table WHERE $column = :$column";

        // Debugging
        if ($this->debug) {
            $data[$column] = $value;
            $this->show_query($sql, $data, $this->query_caveat);
        }

        // Execute the query
        $result = $this->prepare_and_execute($sql, [$column => $value]);

        // Handle query execution result
        if ($result) {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Retrieves records from a database table where the column's value is within a specified array of values.
     *
     * @param string $column The name of the column to filter by.
     * @param array $values The array of values to match against the specified column.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @param string $return_type (optional) The type of result to return ('object' or 'array'). Default is 'object'.
     * @return array Returns an array of objects or arrays representing the fetched rows.
     * @throws InvalidArgumentException If the values array is empty.
     */
    public function get_where_in(string $column, array $values, ?string $target_table = null, string $return_type = 'object'): array {
        if (empty($values)) {
            throw new InvalidArgumentException('The values array must not be empty.');
        }

        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Convert the array of values to a comma-separated string
        $values_str = implode(',', array_map('intval', $values));

        // Build the SQL query
        $sql = "SELECT * FROM $target_table WHERE $column IN ($values_str)";

        // Debugging: show query if debug mode is enabled
        if ($this->debug == true) {
            $data = [];
            $this->show_query($sql, $data, $this->query_caveat);
        }

        // Execute the query
        $this->prepare_and_execute($sql, []);

        // Fetch and return the results
        if ($return_type === 'object') {
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } elseif ($return_type === 'array') {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Counts the number of rows in a database table.
     *
     * @param string|null $target_tbl (optional) The name of the database table to count rows from. Default is null.
     * @return int The number of rows in the specified table.
     * @throws RuntimeException If the query execution fails.
     */
    public function count(?string $target_tbl = null): int {
        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        $sql = "SELECT COUNT(*) AS total_rows FROM $target_tbl";

        // Debugging
        if ($this->debug) {
            $this->show_query($sql, [], $this->query_caveat);
        }

        // Execute the query
        $stmt = $this->dbh->query($sql);

        if ($stmt === false) {
            throw new RuntimeException("Failed to execute query: $sql");
        }

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            throw new RuntimeException("Failed to fetch result for query: $sql");
        }

        return (int) $result['total_rows'];
    }

    /**
     * Counts the number of rows in a database table based on custom conditions.
     *
     * @param string $column The name of the table column referred to when fetching results.
     * @param mixed $value The value that should be matched against the target table column.
     * @param string $operator (optional) The comparison operator. Default is '='.
     * @param string|null $target_tbl (optional) The name of the database table to be queried. Default is null.
     * @return int The number of rows matching the conditions.
     * @throws InvalidArgumentException If an invalid operator is provided.
     * @throws RuntimeException If the query execution fails.
     */
    public function count_where(string $column, $value, string $operator = '=', ?string $target_tbl = null): int {
        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        // Validate operator
        $valid_operators = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE'];
        if (!in_array($operator, $valid_operators)) {
            throw new InvalidArgumentException("Invalid operator: $operator");
        }

        // Adjust value for LIKE operator
        if (in_array($operator, ['LIKE', 'NOT LIKE'])) {
            $value = '%' . $value . '%';
        }

        // Build the SQL query
        $sql = "SELECT COUNT(*) AS total_rows FROM $target_tbl WHERE $column $operator :$column";
        
        // Debugging
        if ($this->debug) {
            $this->show_query($sql, [$column => $value], $this->query_caveat);
        }

        // Execute the query
        $result = $this->prepare_and_execute($sql, [$column => $value]);

        // Handle query execution result
        if ($result) {
            $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total_rows'];
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Counts the number of rows in a database table based on a single condition.
     *
     * @param string $column The name of the table column referred to when fetching results.
     * @param mixed $value The value that should be matched against the target table column.
     * @param string|null $target_table (optional) The name of the database table to be queried. Default is null.
     * @return int The number of rows matching the condition.
     * @throws RuntimeException If the query execution fails.
     */
    public function count_rows(string $column, $value, ?string $target_table = null): int {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Build the SQL query
        $sql = "SELECT COUNT(*) as total FROM $target_table WHERE $column = :$column";
        $data = [$column => $value];

        // Debugging
        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
        }

        // Execute the query
        $result = $this->prepare_and_execute($sql, $data);

        // Handle query execution result
        if ($result) {
            $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $row['total'];
        } else {
            throw new RuntimeException("Failed to execute query: $sql");
        }
    }

    /**
     * Retrieves the maximum 'id' value from the specified database table.
     *
     * @param string|null $target_table (optional) The name of the database table to query. Default is null.
     * @return int|null Returns the maximum 'id' value from the table. Returns 0 if the table is empty or null if no table is specified.
     */
    public function get_max(?string $target_table = null): ?int {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Construct the SQL query to fetch the maximum 'id' value
        $sql = "SELECT MAX(id) AS max_id FROM $target_table";

        // Prepare and execute the query
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();

        // Fetch the maximum 'id' value
        $max_id = $stmt->fetchColumn();

        // If the result is false (indicating an empty table), return 0
        // Otherwise, return the maximum 'id' value as an integer
        return $max_id !== false ? (int) $max_id : 0;
    }

    /**
     * Insert a new record into the database table and return the ID of the newly inserted record.
     *
     * @param array $data An associative array containing column names as keys and their corresponding values.
     * @param string|null $target_table (optional) The name of the database table to insert into. Default is null.
     * @return int|null The ID (int) of the newly inserted record, or null if insertion fails.
     * @throws RuntimeException If the query execution fails.
     */
    public function insert(array $data, ?string $target_table = null): ?int {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Construct the SQL query
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $target_table ($columns) VALUES ($placeholders)";

        // Prepare and execute the query
        $result = $this->prepare_and_execute($sql, $data);

        // Return the ID of the newly inserted record, or null if insertion fails
        if ($result) {
            return (int) $this->dbh->lastInsertId();
        } else {
            return null;
        }
    }

    /**
     * Update a record in the database table.
     *
     * @param int $update_id The ID of the record to update.
     * @param array $data An associative array containing column names as keys and their corresponding values.
     * @param string|null $target_table (optional) The name of the database table to update. Default is null.
     * @return bool True if the update was successful, false otherwise.
     * @throws RuntimeException If the query execution fails.
     */
    public function update(int $update_id, array $data, ?string $target_table = null): bool {
        if (!isset($target_table)) {
            $target_table = $this->get_table_from_url();
        }

        // Construct the SET part of the SQL query
        $set_columns = [];
        foreach ($data as $key => $value) {
            $set_columns[] = "`$key` = :$key";
        }
        $set_clause = implode(', ', $set_columns);

        // Construct the WHERE part of the SQL query
        $where_clause = "`$target_table`.`id` = :id";

        // Construct the full SQL query
        $sql = "UPDATE `$target_table` SET $set_clause WHERE $where_clause";

        // Include the update ID in the data array
        $data['id'] = $update_id;

        // Prepare and execute the query
        return $this->prepare_and_execute($sql, $data);
    }

    /**
     * Updates rows in a database table based on a specific condition.
     *
     * @param string $column The column to match for the condition.
     * @param mixed $column_value The value to match for the condition.
     * @param array $data The data to be updated.
     * @param string|null $target_tbl (optional) The name of the database table. Default is null.
     * @return bool Indicates whether the update operation was successful.
     * @throws RuntimeException If the query execution fails.
     */
    public function update_where(string $column, $column_value, array $data, ?string $target_tbl = null): bool {
        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        // Construct the SQL query
        $sql = "UPDATE `$target_tbl` SET ";
        foreach ($data as $key => $value) {
            $sql .= "`$key` = :$key, ";
        }
        $sql = rtrim($sql, ', ');
        $sql .= " WHERE `$target_tbl`.`$column` = :value";

        // Append the column value to the data array
        $data['value'] = $column_value;

        // Execute the query
        try {
            $this->prepare_and_execute($sql, $data);
            return true; // Update successful
        } catch (Exception $e) {
            throw new RuntimeException("Failed to execute query: $sql. Error: " . $e->getMessage());
        }
    }

    /**
     * Deletes a record from a database table based on its ID.
     *
     * @param int $id The ID of the record to delete.
     * @param string|null $target_tbl (optional) The name of the database table. Default is null.
     * @return bool Indicates whether the delete operation was successful.
     * @throws RuntimeException If the query execution fails.
     */
    public function delete(int $id, ?string $target_tbl = null): bool {
        if (!isset($target_tbl)) {
            $target_tbl = $this->get_table_from_url();
        }

        // Construct the SQL query
        $sql = "DELETE FROM `$target_tbl` WHERE id = :id";
        
        // Prepare data for execution
        $data = ['id' => $id];

        // Execute the query
        try {
            $this->prepare_and_execute($sql, $data);
            return true; // Deletion successful
        } catch (Exception $e) {
            throw new RuntimeException("Failed to execute query: $sql. Error: " . $e->getMessage());
        }
    }

    /**
     * Execute a custom SQL query.
     *
     * @param string $sql The SQL query to execute.
     * @param string|null $return_type (optional) The type of result to return ('object' or 'array'). Default is null.
     * @return array|object|null|mixed Returns the result of the query based on the specified return type.
     * @throws RuntimeException If the query execution fails.
     * @throws InvalidArgumentException If the SQL query is potentially vulnerable to SQL injection.
     * @note It's important to ensure that the provided SQL query is properly sanitized to prevent SQL injection attacks.
     */
    public function query(string $sql, ?string $return_type = null): mixed {

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

        // Return null for cases where no result type is expected
        return null;
    }

    /**
     * Execute a custom SQL query with parameter binding.
     *
     * @param string $sql The SQL query to execute.
     * @param array $data An associative array of parameters to bind to the query.
     * @param string|null $return_type (optional) The type of result to return ('object' or 'array'). Default is null.
     * @return array|object|null Returns the result of the query based on the specified return type.
     * @throws RuntimeException If the query execution fails.
     */
    public function query_bind(string $sql, array $data, ?string $return_type = null): mixed {
        if ($this->debug) {
            $this->show_query($sql, $data, $this->query_caveat);
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
     * Checks if a table exists in the database.
     *
     * @param string $table_name The name of the table to check.
     * @return bool Returns true if the table exists, false otherwise.
     */
    public function table_exists(string $table_name): bool {
        try {
            // Construct the SQL query to check for the table's existence
            $sql = "SHOW TABLES LIKE :table_name";

            // Prepare the statement
            $stmt = $this->dbh->prepare($sql);

            // Bind the table name parameter
            $stmt->bindParam(':table_name', $table_name, PDO::PARAM_STR);

            // Execute the statement
            $stmt->execute();

            // Fetch the result
            $result = $stmt->fetch(PDO::FETCH_NUM);

            // Return true if a result is found, otherwise return false
            return $result !== false;
        } catch (PDOException $e) {
            // Handle any PDO exceptions
            $this->error = $e->getMessage();
            echo $this->error;
            return false;
        }
    }

    /**
     * Retrieves all table names from the database.
     *
     * @return array Returns an array of table names.
     */
    public function get_all_tables(): array {
        try {
            // Construct the SQL query to retrieve all table names
            $sql = "SHOW TABLES";

            // Prepare and execute the statement
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute();

            // Fetch all table names
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Return the array of table names
            return $tables;
        } catch (PDOException $e) {
            // Handle any PDO exceptions
            $this->error = $e->getMessage();
            echo $this->error;
            return [];
        }
    }

    /**
     * Describes the structure of a database table.
     *
     * Retrieves information about the columns of the specified table.
     *
     * @param string $table The name of the table.
     * @param bool $column_names_only (optional) Whether to return only column names. Default is false.
     * @return array|false Returns an array of column details or an array of column names if $column_names_only is true. Returns false on failure.
     */
    public function describe_table(string $table, bool $column_names_only = false): array|false {
        try {
            $sql = 'DESCRIBE ' . $table;
            $columns = $this->query($sql, 'array');

            if ($column_names_only) {
                return array_column($columns, 'Field');
            } else {
                return $columns;
            }
        } catch (PDOException $e) {
            // Handle any PDO exceptions
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Resequence IDs of a specified table.
     *
     * This method resequences the IDs in the given table, assigning new sequential IDs
     * starting from 1. It uses a temporary column to store the new IDs and avoid
     * potential ID conflicts. If the table is empty, the method resets the auto-increment
     * value to 1.
     *
     * @param string $table_name The name of the table to resequence IDs for.
     * @return bool True upon successful resequencing.
     * @throws Exception If the operation fails.
     *
     * @note This method should be used with caution and may produce undesired consequences.
     *       Resequencing IDs can lead to potential data inconsistencies and unexpected behaviors,
     *       especially in systems with complex relationships or when dealing with large datasets.
     *       It's recommended to thoroughly test this method in a controlled environment
     *       before applying it to a production system. Additionally, make sure to take
     *       proper backups of your data before executing this operation.
     */
    public function resequence_ids(string $table_name): bool {

        $num_rows = $this->count($table_name);
        if ($num_rows === 0) {
            return true;
        }
        
        try {
            // Begin transaction
            $this->dbh->beginTransaction();

            // Fetch all rows ordered by current ID
            $rows = $this->get('id', $table_name);

            // Initialize new counter starting from 1
            $counter = 1;

            // If the table is empty, reset auto-increment value to 1
            if (empty($rows)) {
                $this->dbh->exec("ALTER TABLE $table_name AUTO_INCREMENT = 1");
                // Commit transaction and exit
                $this->dbh->commit();
                return true;
            }

            // First pass: assign temporary IDs to avoid conflicts
            foreach ($rows as $row) {
                $id = $row->id;
                $temp_id = -$counter; // Use negative numbers for temporary IDs
                $stmt = $this->dbh->prepare("UPDATE $table_name SET id = :temp_id WHERE id = :id");
                $stmt->execute([':temp_id' => $temp_id, ':id' => $id]);
                $counter++;
            }

            // Second pass: assign new sequential IDs
            $counter = 1;
            foreach ($rows as $row) {
                $temp_id = -$counter; // Temporary IDs were used in the first pass
                $new_id = $counter;
                $stmt = $this->dbh->prepare("UPDATE $table_name SET id = :new_id WHERE id = :temp_id");
                $stmt->execute([':new_id' => $new_id, ':temp_id' => $temp_id]);
                $counter++;
            }

            // Commit transaction
            $this->dbh->commit();
            
            // Return true upon successful resequencing
            return true;
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $this->dbh->rollBack();
            throw $e;
        }
    }

    /**
     * Insert multiple records into the specified table in a batch.
     *
     * This method inserts multiple records into the specified table using a batch insert
     * SQL statement. It's important to ensure that this method is not exposed to website
     * visitors to prevent potential security vulnerabilities.
     *
     * @param string $table The name of the table to insert records into.
     * @param array $records An array containing associative arrays representing records to be inserted.
     * @return int The number of records successfully inserted.
     * @throws PDOException If an error occurs during the database operation.
     * 
     * @note This method should only be used in controlled environments and not exposed to untrusted users.
     */
    public function insert_batch(string $table, array $records): int {
        try {
            // Retrieve field names from the first record
            $fields = array_keys($records[0]);

            // Generate placeholders for prepared statement
            $placeHolders = implode(',', array_fill(0, count($fields), '?'));

            // Flatten the values array for execution
            $values = [];
            foreach ($records as $record) {
                $values = array_merge($values, array_values($record));
            }

            // Construct the SQL query
            $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES ';
            $sql .= implode(',', array_fill(0, count($records), "($placeHolders)"));

            // Prepare and execute the SQL statement
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute($values);

            // Return the number of rows affected (i.e., the number of records inserted)
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Propagate PDO exceptions
            throw $e;
        }
    }

    /**
     * Execute a SQL statement.
     *
     * This method is used to execute a SQL statement. It's primarily intended for
     * usage by Trongate's Module Import Wizard and should not be used in production environments.
     *
     * @param string $sql The SQL statement to execute.
     * @throws Exception If the application environment is not set to 'dev'.
     * @throws PDOException If an error occurs during the database operation.
     *
     * @note This method should only be used for development purposes and may produce undesired consequences if used improperly. It is disabled in production environments.
     */
    public function exec(string $sql): void {
        if (ENV === 'dev') {
            try {
                $this->query($sql);
            } catch (PDOException $e) {
                // Propagate PDO exceptions
                throw $e;
            }
        } else {
            throw new Exception("This feature is disabled because the application environment is not set to 'dev'.");
        }
    }

    /**
     * Display the SQL query to be executed.
     *
     * @param string $query The SQL query to be executed.
     * @param array $data The data to be bound to the SQL query.
     * @param string|null $caveat (optional) Additional information or note about the query. Default is null.
     * @return void
     */
    private function show_query(string $query, array $data, ?string $caveat = null): void {
        $keys = array();
        $values = $data;
        $named_params = true;

        // Build a regular expression for each parameter
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
                $named_params = false;
            }

            if (is_string($value)) {
                $values[$key] = "'" . $value . "'";
            }

            if (is_array($value)) {
                $values[$key] = "'" . implode("','", $value) . "'";
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        if ($named_params == true) {
            $query = preg_replace($keys, $values, $query);
        } else {
            $query .= ' ';
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
     * Determines the PDO parameter type based on the PHP value type.
     *
     * @param mixed $value The value for which to determine the parameter type.
     * @return int The PDO parameter type.
     */
    private function get_param_type(mixed $value): int {
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
     * Prepares and executes a SQL statement with optional data bindings.
     *
     * @param string $sql The SQL statement to prepare.
     * @param array $data (optional) The data to bind to the SQL statement. Default is an empty array.
     * @return bool True on success, false on failure.
     */
    private function prepare_and_execute(string $sql, array $data = []): bool {
        $this->stmt = $this->dbh->prepare($sql);

        if (isset($data[0])) { //unnamed data
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
     * Retrieves the table name from the first URL segment or the current module.
     *
     * @return string The table name retrieved from the URL segment or the current module.
     */
    private function get_table_from_url(): string {
        return isset($this->current_module) ? $this->current_module : segment(1);
    }

    /**
     * Adds LIMIT and OFFSET clauses to the SQL statement if provided.
     *
     * @param string $sql The SQL statement to which LIMIT and OFFSET clauses are added.
     * @param int|null $limit (optional) The maximum number of results to return. Default is null.
     * @param int|null $offset (optional) The number of rows to skip before fetching results. Default is null.
     * @return string The SQL statement with LIMIT and OFFSET clauses added if provided.
     */
    private function add_limit_offset(string $sql, ?int $limit, ?int $offset): string {
        if ((is_numeric($limit)) && (is_numeric($offset))) {
            $limit_results = true;
            $sql .= " LIMIT $offset, $limit";
        }

        return $sql;
    }

}