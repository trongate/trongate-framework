<?php

declare(strict_types=1);

class Transferer
{
    public function __construct()
    {
        if (ENV !== 'dev') {
            exit;
        }
    }

    public function process_post(): void
    {
        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (! isset($data->action)) {
            exit;
        }

        if (isset($data->controllerPath) && ($data->action === 'viewSql')) {
            readfile($data->controllerPath);
            exit;
        }

        if (isset($data->targetFile) && ($data->action === 'deleteFile')) {
            $result = $this->delete_file($data->targetFile);

            if ($result === '') {
                echo 'Finished.';
            }

            exit;
        }

        if (isset($data->sqlCode) && (isset($data->targetFile)) && ($data->action === 'runSql')) {
            $this->run_sql($data->sqlCode);
            $this->delete_file($data->targetFile);
            exit;
        }

        if (isset($data->sampleFile) && ($data->action === 'getFinishUrl')) {
            $this->get_finish_location($data->sampleFile);
            exit;
        }
    }

    public function check_sql($file_contents)
    {
        $file_contents = strtolower($file_contents);

        $dangerous_strings[] = 'drop ';
        $dangerous_strings[] = 'update ';
        $dangerous_strings[] = 'truncate ';
        $dangerous_strings[] = 'delete from';

        foreach ($dangerous_strings as $dangerous_string) {
            $contains_dangerous_string = $this->contains_needle($dangerous_string, $file_contents);
            if ($contains_dangerous_string === true) {
                return false;
            }
        }

        return true;
    }

    private function contains_needle($needle, $haystack)
    {
        $pos = strpos($haystack, $needle);

        if ($pos === false) {
            return false;
        }
        return true;
    }

    private function run_sql($sql): void
    {
        $model_file = '../engine/Model.php';

        $rand_str = make_rand_str(32);
        $sql = str_replace('Tz8tehsWsTPUHEtzfbYjXzaKNqLmfAUz', $rand_str, $sql);

        require_once $model_file;
        $model = new Model();
        $model->exec($sql);
        echo 'Finished.';
    }

    private function delete_file($filepath): void
    {
        if (file_exists($filepath) && (is_writable($filepath))) {
            unlink($filepath);
        } else {
            http_response_code(403);
            echo $filepath;
            exit;
        }
    }

    private function get_finish_location($sample_file): void
    {
        //get the directory path
        $bits = explode('/', $sample_file);
        unset($bits[4]);
        unset($bits[3]);

        $files = [];
        $dir_path = $bits[0].'/'.$bits[1].'/'.$bits[2].'/';

        if (file_exists($dir_path)) {
            $files = [];
            foreach (glob($dir_path.'*.sql') as $file) {
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
