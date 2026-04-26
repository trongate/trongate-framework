<?php
class Webhooks extends Trongate {

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        $this->set_cors_headers();
        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            echo '403 Forbidden - Endpoint disabled since not in dev mode';
            die();
        }
    }

    /**
     * Set CORS headers to allow cross-origin requests from trusted origins.
     *
     * The query builder runs inside an iframe on trongate.io and makes AJAX
     * requests back to this local webhook endpoint. Without these headers,
     * the browser blocks the cross-origin request.
     *
     * Dynamically allows only whitelisted origins, with a fallback error
     * response for unauthorized origins.
     *
     * @return void
     */
    private function set_cors_headers(): void {
        header('Vary: Origin');

        $allowed_origins = [
            'https://trongate.io',
            'http://localhost'
        ];

        if (!isset($_SERVER['HTTP_ORIGIN'])) {
            return;
        }

        $origin = $_SERVER['HTTP_ORIGIN'];

        if (in_array($origin, $allowed_origins, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'CORS origin not allowed']);
            exit;
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, trongate-mx-request, X-Window-Type');
        header('Access-Control-Allow-Private-Network: true');
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // Chrome's Private Network Access requires the preflight to
            // explicitly echo back the Access-Control-Request-Private-Network
            // header with a value of "true" to allow loopback requests.
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_PRIVATE_NETWORK'])) {
                header('Access-Control-Allow-Private-Network: true');
            }
            http_response_code(204);
            exit;
        }
    }

    public function inbound() {
        $action = post('action', true);

        switch ($action) {
            case 'do this task':
                $this->do_this_task();
                break;
            case 'get_tables':
                $this->get_tables();
                break;
            case 'execute sql':
                $this->execute_sql();
                break;
            default:
                $this->error_unknown_action($action);
                break;
        }
    }

    private function get_tables(): void {
        try {
            $rows = $this->db->query(
                "SELECT TABLE_NAME FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' 
                 ORDER BY TABLE_NAME",
                'object'
            );

            $tables = [];

            foreach ($rows as $row) {
                $table_name = $row->TABLE_NAME;

                $col_rows = $this->db->query_bind(
                    "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table 
                     ORDER BY ORDINAL_POSITION",
                    ['table' => $table_name],
                    'object'
                );

                $columns = [];
                foreach ($col_rows as $col) {
                    $columns[] = $col->COLUMN_NAME;
                }

                $tables[] = [
                    'id' => $table_name,
                    'columns' => $columns
                ];
            }

            http_response_code(200);
            echo json_encode($tables);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'code' => 500,
                'phrase' => 'Database Error',
                'info' => 'Failed to fetch table data: ' . $e->getMessage()
            ]);
        }
    }

    private function execute_sql(): void {
        try {
            $sql = post('sql');
            if (empty($sql)) {
                throw new Exception('SQL parameter is required');
            }

            $params_json = post('params');
            $params = [];

            if (!empty($params_json)) {
                $params = json_decode($params_json, true);
                if ($params === null) {
                    throw new Exception('Invalid JSON in params parameter');
                }
                if (!is_array($params)) {
                    throw new Exception('params must be a JSON object/array');
                }
            }

            if (!empty($params)) {
                $results = $this->db->query_bind($sql, $params, 'array');
            } else {
                $results = $this->db->query($sql, 'array');
            }

            http_response_code(200);
            echo json_encode($results);
        } catch (Exception $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    private function do_this_task(): void {
        $params = [
            'job_code' => post('job_code', true),
            'task_code' => post('task_code', true),
            'task_number' => (int) post('task_number')
        ];

        $this->module('trongate_control-flo');
        $api_base_url = $this->flo->api_base_url;
        $target_url = $api_base_url . 'evo/fetch_task_details';

        $response = $this->submit_post_request($target_url, $params);

        if ($response['http_code'] !== 200) {
            $this->error_general($response['http_code'], $response['response']);
        }

        $response_obj = json_decode($response['response']);
        $action = $response_obj->action ?? '';
        $this->execute_task_action($action, $response_obj);
    }

    private function execute_task_action(string $action, object $response_obj): void {
        switch ($action) {
            case 'create directory':
                $this->create_directory($response_obj);
                break;
            case 'write file':
                $this->write_file($response_obj);
                break;
            case 'execute sql':
                $this->execute_sql_from_task($response_obj);
                break;
            default:
                echo 'unknown action! - you submitted an action of ' . $action;
                break;
        }
    }

    private function create_directory(object $response_obj): void {
        $dir_path = $response_obj->dir_path ?? '';
        if ($dir_path === '') {
            $this->error_general(400, '', [
                'code' => 400,
                'phrase' => 'Bad Request',
                'info' => 'The dir_path parameter is required.'
            ]);
        }

        $full_dir_path = APPPATH . $dir_path;
        if ($this->file->exists($full_dir_path)) {
            $this->error_general(409, '', [
                'code' => 409,
                'phrase' => 'Conflict',
                'info' => 'The directory already exists at: ' . $dir_path
            ]);
        }

        try {
            $this->file->create_directory($full_dir_path, 0777);
            $task_code = $response_obj->task_code ?? '';
            $this->task_complete($task_code);
        } catch (Exception $e) {
            $this->error_general(403, '', [
                'code' => 403,
                'phrase' => 'Forbidden',
                'info' => 'Permission denied: Unable to create directory. ' . $e->getMessage()
            ]);
        }
    }

    private function write_file(object $response_obj): void {
        $file_path = $response_obj->file_path ?? '';
        $file_content = $response_obj->file_content ?? '';
        $task_code = $response_obj->task_code ?? '';

        if ($file_path === '') {
            $this->error_general(400, '', [
                'code' => 400,
                'phrase' => 'Bad Request',
                'info' => 'The file_path parameter is required.'
            ]);
        }

        if ($file_content === '') {
            $this->error_general(400, '', [
                'code' => 400,
                'phrase' => 'Bad Request',
                'info' => 'The file_content parameter is required.'
            ]);
        }

        $full_file_path = APPPATH . $file_path;
        $dir_path = dirname($full_file_path);
        if (!$this->file->exists($dir_path)) {
            $this->error_general(404, '', [
                'code' => 404,
                'phrase' => 'Directory Not Found',
                'info' => 'Parent directory does not exist.'
            ]);
        }

        if ($this->file->exists($full_file_path)) {
            $filename = get_last_part($file_path, '/');
            $this->error_general(409, '', [
                'code' => 409,
                'phrase' => 'Conflict',
                'info' => 'The file ' . $filename . ' already exists.'
            ]);
        }

        try {
            $this->file->write($full_file_path, $file_content);
            $this->task_complete($task_code);
        } catch (Exception $e) {
            $filename = get_last_part($file_path, '/');
            $this->error_general(500, '', [
                'code' => 500,
                'phrase' => 'Write Failed',
                'info' => 'Unable to write ' . $filename . '. Please check directory permissions.'
            ]);
        }
    }

    private function execute_sql_from_task(object $response_obj): void {
        $sql = $response_obj->sql ?? '';
        $task_code = $response_obj->task_code ?? '';
        if ($sql === '') {
            $this->error_general(400, '', [
                'code' => 400,
                'phrase' => 'Bad Request',
                'info' => 'The sql parameter is required.'
            ]);
        }
        try {
            $this->db->query($sql);
            $this->task_complete($task_code);
        } catch (Exception $e) {
            $this->error_general(500, '', [
                'code' => 500,
                'phrase' => 'SQL Execution Failed',
                'info' => 'Unable to execute database operation.'
            ]);
        }
    }

    private function task_complete(string $task_code): void {
        http_response_code(200);
        echo json_encode([
            'action' => 'task complete',
            'task_code' => $task_code
        ]);
    }

    public function error_general(int $http_code, string $response_body, ?array $fallback_error_data = null): void {
        http_response_code($http_code);
        $error_data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (isset($fallback_error_data)) {
                $error_data = $fallback_error_data;
            } else {
                $error_data = [
                    'code' => 400,
                    'phrase' => 'Unknown Error',
                    'info' => 'The server was unable to respond to your request due to an unknown error.'
                ];
            }
        }
        echo json_encode($error_data);
        die();
    }

    private function error_unknown_action(string $action): void {
        http_response_code(400);
        echo json_encode([
            'code' => 400,
            'phrase' => 'Unknown Action',
            'info' => 'The action "' . $action . '" is not recognized.'
        ]);
    }

    public function list_mods(): void {
        $all_modules = $this->get_directories('modules', true);
        http_response_code(200);
        echo json_encode($all_modules);
    }

    private function get_directories(string $subdirectory, bool $names_only = false): array {
        $target_path = APPPATH . $subdirectory;
        $directories = [];
        if (is_dir($target_path)) {
            $items = scandir($target_path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                if (is_dir($target_path . '/' . $item)) {
                    $directories[] = $names_only ? $item : $target_path . '/' . $item;
                }
            }
        }
        return $directories;
    }

    private function submit_post_request(string $target_url, array $params): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['http_code' => $http_code, 'response' => $response];
    }

}
