<?php
class Transferer
{
    function __construct() {
        if (ENV != 'dev') {
            die();
        }
    }
   
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
        require_once $model_file;
        $model = new Model;
        $model->exec($sql);
        echo 'Finished.';
    }

    private function delete_file($filepath) {
        if ((file_exists($filepath)) && (is_writable($filepath))) {
            unlink($filepath);
        } else {
            http_response_code(403);
            echo $filepath; die();
        }
    }

    private function get_finish_location($sample_file) {

        //get the directory path
        $bits = explode('/', $sample_file);
        unset($bits[4]);
        unset($bits[3]);

        $files = array();
        $dir_path = $bits[0].'/'.$bits[1].'/'.$bits[2].'/';

        if (file_exists($dir_path)) {
            $files = array();
            foreach (glob($dir_path."*.sql") as $file) {
                $files[] = $file;
            }
        }

        if (count($files)>0) {
            echo 'current_url';
        } else {
            echo BASE_URL;
        }

    }

}