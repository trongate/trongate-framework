<?php

/**
 * Class Model
 *
 * A generic model class for interacting with a MySQL database using PDO.
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
     * Constructor method for Model class.
     *
     * @param string|null $current_module The current module.
     */
    public function __construct($current_module = null) {

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
     * Retrieves the parameter type for binding.
     *
     * @param mixed $value The value to check.
     *
     * @return int The parameter type.
     */
    private function get_param_type($value) {

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
     * Prepares and executes a SQL query.
     *
     * @param string $sql  The SQL query.
     * @param array  $data The data to bind.
     *
     * @return mixed The query result.
     */
    function prepare_and_execute($sql, $data) {

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
     * Retrieves the table name from the URL.
     *
     * @return string The table name.
     */
    private function get_table_from_url() {
        // Use $this->current_module if set, otherwise, use the first URL segment
        return isset($this->current_module) ? $this->current_module : segment(1);
    }


    /**
     * Corrects the tablename format.
     *
     * @param string $target_tbl The target table.
     *
     * @return string The corrected tablename.
     */
    private function correct_tablename($target_tbl) {
        $bits = explode('-', $target_tbl);
        $num_bits = count($bits);
        if ($num_bits > 1) {
            $target_tbl = $bits[$num_bits - 1];
        }

        return $target_tbl;
    }


    /**
     * Adds limit and offset to the SQL query.
     *
     * @param string   $sql    The SQL query.
     * @param int|null $limit  The limit.
     * @param int|null $offset The offset.
     *
     * @return string The modified SQL query.
     */
    private function add_limit_offset($sql, $limit, $offset) {

        if ((is_numeric($limit)) && (is_numeric($offset))) {
            $limit_results = true;
            $sql .= " LIMIT $offset, $limit";
        }

        return $sql;
    }


    /**
     * Retrieves all tables from the database.
     *
     * @return array The list of tables.
     */
    protected function _get_all_tables() {
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
     * Retrieves data from a table.
     *
     * @param string|null $order_by   The column to order by.
     * @param string|null $target_tbl The target table.
     * @param int|null    $limit      The limit.
     * @param int|null    $offset     The offset.
     *
     * @return array The query result.
     */
    public function get($order_by = null, $target_tbl = null, $limit = null, $offset = null) {

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
     * Retrieves data based on custom conditions.
     *
     * @param string      $column     The column name.
     * @param mixed       $value      The value to compare.
     * @param string|null $operator   The comparison operator.
     * @param string|null $order_by   The column to order by.
     * @param string|null $target_tbl The target table.
     * @param int|null    $limit      The limit.
     * @param int|null    $offset     The offset.
     *
     * @return array The query result.
     */
    public function get_where_custom($column, $value, $operator = '=', $order_by = 'id', $target_tbl = null, $limit = null, $offset = null) {

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
     * Retrieves a single record.
     *
     * @param int         $id         The record ID.
     * @param string|null $target_tbl The target table.
     *
     * @return object|null The query result.
     */
    public function get_where($id, $target_tbl = null) {

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
     * Retrieves a single record with custom conditions.
     *
     * @param string      $column     The column name.
     * @param mixed       $value      The value to compare.
     * @param string|null $target_tbl The target table.
     *
     * @return object|null The query result.
     */
    public function get_one_where($column, $value, $target_tbl = null) {
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
     * Retrieves multiple records with custom conditions.
     *
     * @param string      $column     The column name.
     * @param mixed       $value      The value to compare.
     * @param string|null $target_tbl The target table.
     *
     * @return array The query result.
     */
    public function get_many_where($column, $value, $target_tbl = null) {

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
     * @param string|null $target_tbl The target table.
     *
     * @return int The number of rows.
     */
    public function count($target_tbl = null) {
        //return number of rows on a table

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
     * Counts the number of rows in a table with custom conditions.
     *
     * @param string      $column     The column name.
     * @param mixed       $value      The value to compare.
     * @param string      $operator   The comparison operator.
     * @param string|null $order_by   The column to order by.
     * @param string|null $target_tbl The target table.
     * @param int|null    $limit      The limit.
     * @param int|null    $offset     The offset.
     *
     * @return int The number of rows.
     */
    public function count_where($column, $value, $operator = '=', $order_by = 'id', $target_tbl = null, $limit = null, $offset = null) {
        //return number of rows on table (with query customisation)

        $query = $this->get_where_custom($column, $value, $operator, $order_by, $target_tbl, $limit, $offset);
        $num_rows = count($query);
        return $num_rows;
    }


    /**
     * Counts the number of rows in a table based on a single condition.
     *
     * @param string      $column     The column name.
     * @param mixed       $value      The value to compare.
     * @param string|null $target_tbl The target table.
     *
     * @return int The number of rows.
     */
    public function count_rows($column, $value, $target_tbl = null) {
        //simplified version of count_where (accepts one condition)

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
     * Retrieves the maximum value of a column in a table.
     *
     * @param string|null $target_tbl The target table.
     *
     * @return int The maximum value.
     */
    public function get_max($target_tbl = null) {

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
     * Shows the SQL query to be executed.
     *
     * @param string      $query   The SQL query.
     * @param array       $data    The data to bind.
     * @param string|null $caveat  Additional information.
     */
    public function show_query($query, $data, $caveat = null) {
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
     * Inserts data into a table.
     *
     * @param array       $data       The data to insert.
     * @param string|null $target_tbl The target table.
     *
     * @return mixed The last inserted ID.
     */
    public function insert($data, $target_tbl = null) {

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
     * Updates data in a table.
     *
     * @param int         $update_id  The ID to update.
     * @param array       $data       The data to update.
     * @param string|null $target_tbl The target table.
     */
    public function update($update_id, $data, $target_tbl = null) {

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
     * Updates data in a table based on a specific condition.
     *
     * @param string      $column        The column to match against.
     * @param mixed       $column_value  The value to match against.
     * @param array       $data          The data to update.
     * @param string|null $target_tbl    The target table.
     */
    public function update_where($column, $column_value, $data, $target_tbl = null) {

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
     * Deletes a record from a table.
     *
     * @param int         $id           The ID of the record to delete.
     * @param string|null $target_tbl   The target table.
     */
    public function delete($id, $target_tbl = null) {

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
     * Executes a raw SQL query.
     *
     * WARNING: This method poses a high risk of SQL injection - use with caution!
     *
     * @param string      $sql          The SQL query to execute.
     * @param string|bool $return_type  The type of result to return ('object' for stdClass objects, 'array' for associative arrays). Default is false.
     *
     * @return mixed The result of the query, either as an object, an array, or false if no return type specified.
     */
    public function query($sql, $return_type = false) {

        //WARNING: very high risk of SQL injection - use with caution!
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
     * Executes a raw SQL query with parameter binding.
     *
     * @param string      $sql          The SQL query to execute.
     * @param array       $data         The data to bind to the query.
     * @param string|bool $return_type  The type of result to return ('object' for stdClass objects, 'array' for associative arrays). Default is false.
     *
     * @return mixed The result of the query, either as an object, an array, or false if no return type specified.
     */
    public function query_bind($sql, $data, $return_type = false) {

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
     * Attempts to truncate a table if it's empty.
     *
     * This method first counts the number of rows in the specified table.
     * If the table is empty, it executes a TRUNCATE command to remove all data from the table.
     *
     * @param string $tablename The name of the table to truncate.
     */
    public function attempt_truncate($tablename) {
        $num_rows = $this->count($tablename);

        if ($num_rows == 0) {
            $sql = 'TRUNCATE ' . $tablename;
            $this->query($sql);
        }
    }


    /**
     * Inserts multiple records into a table in a single batch operation.
     *
     * WARNING: Never let website visitors invoke this method directly!
     *
     * @param string $table   The name of the table to insert records into.
     * @param array  $records An array of associative arrays representing the records to insert.
     *
     * @return int The number of records inserted.
     */
    public function insert_batch($table, array $records) {

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
     * Executes a raw SQL statement.
     *
     * If the environment is set to 'dev', the SQL statement is executed.
     * Otherwise, a message indicating that the feature is disabled is echoed.
     *
     * @param string $sql The SQL statement to execute.
     */

    public function exec($sql) {
        if (ENV == 'dev') {
            //this gets used on auto module table setups
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute();
        } else {
            echo 'Feature disabled, since not on \'dev\' mode.';
        }
    }
}
