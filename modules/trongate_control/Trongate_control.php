<?php
/**
 * Trongate Module Import Wizard - Main Controller
 * Handles SQL file imports for newly imported modules (dev environment only).
 * 
 * This module is invoked via redirect from Core.php when SQL files are detected.
 * Uses session to track the original URL for returning after completion.
 */
class Trongate_control extends Trongate {

    private const MAX_SQL_FILE_SIZE_KB = 1000;

    /**
     * Constructor - ensure dev environment only
     */
    function __construct($module_name = null) {
        parent::__construct($module_name);
        
        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            die();
        }
    }

    /**
     * Main entry point - displays SQL files found in module
     */
    function index() {
        // Get SQL files from the module directory

        $files = $this->get_sql_files();
        
        if (count($files) === 0) {
            $this->finish_and_redirect();
        }
        
        // Build the file list HTML
        $data['files'] = $files;
        $data['file_list_html'] = $this->build_file_list($files);
        $data['current_url'] = current_url();
        $data['base_url'] = BASE_URL;
        $data['first_file'] = isset($files[0]) ? $files[0] : '';
        
        $this->view('main', $data);
    }
    
    /**
     * Handle AJAX POST requests
     */
    function process() {
        $data = $this->get_post_data();
        
        if (!isset($data->action)) {
            http_response_code(400);
            die();
        }
        
        $this->route_action($data);
    }
    
    /**
     * Get SQL files from the original module directory
     * Uses the return URL stored in session to determine which module
     */
    private function get_sql_files(): array {
        // Get the actual module name from session
        if (!isset($_SESSION['tg_target_module'])) {
            return [];
        }
        
        $module_name = $_SESSION['tg_target_module'];
        $module_path = APPPATH . 'modules/' . $module_name . '/';
        
        // Check if it's a child module (parent-child format)
        if (strpos($module_name, '-') !== false) {
            $bits = explode('-', $module_name);
            if (count($bits) === 2) {
                $parent = $bits[0];
                $child = $bits[1];
                $module_path = APPPATH . 'modules/' . $parent . '/' . $child . '/';
            }
        }
        
        if (!file_exists($module_path)) {
            return [];
        }
        
        $files = [];
        foreach (glob($module_path . '*.sql') as $file) {
            $files[] = $file;
        }
        
        return $files;
    }
    
    /**
     * Get the return URL from session
     */
    private function get_return_url(): string {
        if (isset($_SESSION['tg_return_url'])) {
            return $_SESSION['tg_return_url'];
        }
        
        return '';
    }
    
    /**
     * Extract module directory path from URL
     * Example: https://site.com/users/create -> modules/users/
     */
    private function get_module_path_from_url(string $url): string {
        // Remove base URL to get just the segments
        $url_without_base = str_replace(BASE_URL, '', $url);
        $url_without_base = ltrim($url_without_base, '/');
        
        // Get first segment (the module name)
        $segments = explode('/', $url_without_base);
        $module_name = isset($segments[0]) ? $segments[0] : '';
        
        if ($module_name === '') {
            return '';
        }
        
        // Build module path
        $module_path = APPPATH . 'modules/' . $module_name . '/';
        
        // Check if it's a child module (parent-child format)
        if (strpos($module_name, '-') !== false) {
            $bits = explode('-', $module_name);
            if (count($bits) === 2) {
                $parent = $bits[0];
                $child = $bits[1];
                $module_path = APPPATH . 'modules/' . $parent . '/' . $child . '/';
            }
        }
        
        return $module_path;
    }
    
    /**
     * Build HTML list of SQL files with action buttons
     */
    private function build_file_list(array $files): string {
        $count = count($files);
        
        if ($count === 1) {
            $html = '<p>The following SQL file was found within the module directory:</p>';
        } else {
            $html = '<p>The following SQL files were found within the module directory:</p>';
        }
        
        $html .= '<ul>';
        
        foreach ($files as $file) {
            $filename = basename($file);
            $filesize_kb = round(filesize($file) / 1024, 2);
            
            $html .= '<li>* ' . $filename . ' (' . $filesize_kb . ' KB)';
            
            if ($filesize_kb > self::MAX_SQL_FILE_SIZE_KB) {
                $html .= $this->get_file_too_big_button($filename, $file);
            } else {
                $html .= $this->get_file_action_button($file);
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Get button for files that exceed size limit
     */
    private function get_file_too_big_button(string $filename, string $filepath): string {
        $escaped_filename = addslashes($filename);
        $escaped_filepath = addslashes($filepath);
        return '<button class="danger" onclick="explain_too_big(\'' . $escaped_filename . '\', \'' . $escaped_filepath . '\')">TOO BIG!</button>';
    }
    
    /**
     * Get action button based on SQL safety check
     */
    private function get_file_action_button(string $file): string {
        $file_contents = file_get_contents($file);
        $is_safe = $this->model->check_sql_safety($file_contents);
        $escaped_file = addslashes($file);
        
        if ($is_safe === true) {
            return '<button onclick="view_sql(\'' . $escaped_file . '\', false)">VIEW SQL</button>';
        } else {
            return '<button class="warning" onclick="view_sql(\'' . $escaped_file . '\', true)">SUSPICIOUS!</button>';
        }
    }
    
    /**
     * Get and decode POST data
     */
    private function get_post_data(): object {
        $posted_data = file_get_contents('php://input');
        return json_decode($posted_data);
    }
    
    /**
     * Route to appropriate action handler
     */
    private function route_action(object $data): void {
        switch ($data->action) {
            case 'viewSql':
                $this->handle_view_sql($data);
                break;
                
            case 'deleteFile':
                $this->handle_delete_file($data);
                break;
                
            case 'runSql':
                $this->handle_run_sql($data);
                break;
                
            case 'checkFinish':
                $this->handle_check_finish();
                break;
                
            default:
                http_response_code(400);
                die();
        }
    }
    
    /**
     * Handle view SQL action - return file contents
     */
    private function handle_view_sql(object $data): void {
        if (!isset($data->controllerPath)) {
            http_response_code(400);
            die();
        }
        
        if (!file_exists($data->controllerPath)) {
            http_response_code(404);
            die();
        }
        
        readfile($data->controllerPath);
        die();
    }
    
    /**
     * Handle delete file action
     */
    private function handle_delete_file(object $data): void {
        if (!isset($data->targetFile)) {
            http_response_code(400);
            die();
        }
        
        $result = $this->model->delete_sql_file($data->targetFile);
        
        if ($result === true) {
            http_response_code(200);
            echo 'Finished.';
        }
        
        die();
    }
    
    /**
     * Handle run SQL action
     */
    private function handle_run_sql(object $data): void {
        if (!isset($data->sqlCode) || !isset($data->targetFile)) {
            http_response_code(400);
            die();
        }
        
        $this->model->execute_sql($data->sqlCode);
        $this->model->delete_sql_file($data->targetFile);
        die();
    }
    
    /**
     * Handle check finish action - determine if more files remain
     */
    private function handle_check_finish(): void {
        $files = $this->get_sql_files();
        
        if (count($files) > 0) {
            // More files remain - reload current page
            echo 'reload';
        } else {
            // All done - return the finish URL
            $finish_url = $this->get_finish_url();
            echo $finish_url;
        }
        
        die();
    }
    
    /**
     * Get the URL to redirect to when finished
     * Returns the original URL if set in session, otherwise BASE_URL
     */
    private function get_finish_url(): string {
        $return_url = $this->get_return_url();
        
        if ($return_url !== '') {
            return $return_url;
        }
        
        return BASE_URL;
    }
    
    /**
     * Finish processing and redirect
     */
    private function finish_and_redirect(): void {
        $finish_url = $this->get_finish_url();
        
        // Clear session
        unset($_SESSION['tg_return_url']);
        
        // Redirect
        redirect($finish_url);
    }
}