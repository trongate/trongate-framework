<?php

/**
 * Class Transferer - Handles SQL imports made via the Module Import Wizard.
 */
class Transferer {
    public function __construct() {
        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            die();
        }
    }

    /**
     * Processes the incoming POST request and performs actions based on the request data.
     *
     * @return void
     */
    public function process_post(): void {
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
                http_response_code(200);
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
     * Checks if the given SQL file contents contain any dangerous SQL commands.
     *
     * @param string $file_contents The contents of the SQL file to check.
     * @return bool Returns false if dangerous SQL commands are found, otherwise true.
     */
    public function check_sql(string $file_contents): bool {

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
     * Checks if a needle string is contained within a haystack string.
     *
     * @param string $needle The substring to search for.
     * @param string $haystack The string to search within.
     * @return bool Returns true if the needle is found in the haystack, otherwise false.
     */
    private function contains_needle(string $needle, string $haystack): bool {
        $pos = strpos($haystack, $needle);

        if ($pos === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Runs the given SQL command after replacing a specific placeholder string with a random string.
     *
     * @param string $sql The SQL command to execute.
     * @return void
     */
    private function run_sql(string $sql): void {
        $model_file = '../engine/Model.php';

        $rand_str = make_rand_str(32);
        $sql = str_replace('Tz8tehsWsTPUHEtzfbYjXzaKNqLmfAUz', $rand_str, $sql);

        require_once $model_file;
        $model = new Model;
        $model->exec($sql);
        http_response_code(200);
        echo 'Finished.';
    }

    /**
     * Deletes the specified file if it exists and is writable.
     * If the file does not exist or is not writable, it sends a 403 HTTP response code.
     *
     * @param string $filepath The path to the file to be deleted.
     * @return void
     */
    private function delete_file(string $filepath): void {
        if ((file_exists($filepath)) && (is_writable($filepath))) {
            unlink($filepath);
        } else {
            http_response_code(403);
            echo $filepath;
            die();
        }
    }

    /**
     * Determines the finish location based on the given sample file.
     * If SQL files are found in the specified directory, it echoes 'current_url',
     * otherwise it echoes the base URL.
     *
     * @param string $sample_file The sample file path to use for determining the directory.
     * @return void
     */
    private function get_finish_location(string $sample_file): void {

        // Get the directory path
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