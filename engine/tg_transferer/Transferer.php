<?php
class Transferer {
    function __construct() {
        if (ENV != 'dev') {
            die();
        }
    }

    /**
     * Processes POST data received from the client.
     *
     * Reads JSON data from the input stream and performs actions based on the received data.
     * Possible actions include viewing SQL, deleting files, running SQL, and retrieving finish location.
     * Exits script execution after processing the request.
     */
    public function process_post() {

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (!isset($data->action)) {
            die();
        }

        if ((isset($data->controllerPath)) && ($data->action == 'viewSql')) {
            readFile($data->controllerPath);
            die();
        }

        if ((isset($data->targetFile)) && ($data->action == 'deleteFile')) {

            $result = $this->delete_file($data->targetFile);

            if ($result == '') {
                echo 'Finished.';
            }

            die();
        }

        if ((isset($data->sqlCode)) && (isset($data->targetFile)) && ($data->action == 'runSql')) {
            $this->run_sql($data->sqlCode);
            $this->delete_file($data->targetFile);
            die();
        }

        if ((isset($data->sampleFile)) && ($data->action == 'getFinishUrl')) {
            $this->get_finish_location($data->sampleFile);
            die();
        }
    }

    /**
     * Checks if SQL code contains potentially dangerous statements.
     *
     * @param string $file_contents The SQL code to be checked.
     * @return bool Returns true if the SQL code is safe, otherwise returns false.
     */
    public function check_sql($file_contents) {

        $file_contents = strtolower($file_contents);

        $dangerous_strings[] = 'drop ';
        $dangerous_strings[] = 'update ';
        $dangerous_strings[] = 'truncate ';
        $dangerous_strings[] = 'delete from';

        foreach ($dangerous_strings as $dangerous_string) {
            $contains_dangerous_string = $this->contains_needle($dangerous_string, $file_contents);
            if ($contains_dangerous_string == true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a string contains a specific substring.
     *
     * This function determines whether the provided haystack string contains the specified needle substring.
     *
     * @param string $needle The substring to search for.
     * @param string $haystack The string to search within.
     * @return bool Returns true if the needle substring is found within the haystack string, otherwise returns false.
     */
    private function contains_needle($needle, $haystack) {
        $pos = strpos($haystack, $needle);

        if ($pos === false) {
            return false;
        } else {
            return true;
        }
    }

    private function run_sql($sql) {
        $model_file = '../engine/Model.php';

        $rand_str = make_rand_str(32);
        $sql = str_replace('Tz8tehsWsTPUHEtzfbYjXzaKNqLmfAUz', $rand_str, $sql);

        require_once $model_file;
        $model = new Model;
        $model->exec($sql);
        echo 'Finished.';
    }


    /**
     * Runs the provided SQL code.
     *
     * This function replaces a placeholder string with a randomly generated string in the SQL code,
     * requires the Model class file, executes the SQL code using the Model class, and echoes 'Finished.'
     * upon completion.
     *
     *
     * @param string $sql The SQL code to be executed.
     */
    private function delete_file($filepath) {
        if ((file_exists($filepath)) && (is_writable($filepath))) {
            unlink($filepath);
        } else {
            http_response_code(403);
            echo $filepath;
            die();
        }
    }


    /**
     * Retrieves the finish location based on the sample file path.
     *
     * This function extracts the directory path from the provided sample file path,
     * checks if any SQL files exist in the directory, and echoes either 'current_url' or BASE_URL
     * depending on whether SQL files are found in the directory.
     *
     * @param string $sample_file The path of the sample file.
     */
    private function get_finish_location($sample_file) {

        //get the directory path
        $bits = explode('/', $sample_file);
        unset($bits[4]);
        unset($bits[3]);

        $files = array();
        $dir_path = $bits[0] . '/' . $bits[1] . '/' . $bits[2] . '/';

        if (file_exists($dir_path)) {
            $files = array();
            foreach (glob($dir_path . "*.sql") as $file) {
                $files[] = $file;
            }
        }

        if (count($files) > 0) {
            echo 'current_url';
        } else {
            echo BASE_URL;
        }
    }
}
