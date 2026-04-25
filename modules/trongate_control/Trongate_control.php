<?php
/**
 * Trongate Module Import Wizard - Main Controller
 * 
 * Handles SQL file imports for newly imported modules (dev environment only).
 * Provides manifest scanning functionality for package management.
 * 
 * This module is invoked via redirect from Core.php when SQL files are detected.
 * Uses session to track the original URL for returning after completion.
 * 
 * @package Trongate_control
 * @author Trongate Framework
 * @version 2.0.0
 */
class Trongate_control extends Trongate {

    /**
     * Maximum allowed SQL file size in kilobytes
     * 
     * @var int
     */
    private const MAX_SQL_FILE_SIZE_KB = 1000;

    /**
     * Constructor - ensures dev environment only for most methods
     * 
     * Allows the manifests() method to be accessible in any environment
     * for package management purposes.
     * 
     * @param string|null $module_name The name of the module
     * @return void
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        
        // Get current method from URL
        $current_method = segment(2);
        
        // Allow manifests method in any environment
        if (strtolower(ENV) !== 'dev' && $current_method !== 'manifests') {
            http_response_code(403);
            die();
        }
    }

    /**
     * Main entry point - displays SQL files found in module
     * 
     * Shows a list of SQL files discovered in the module directory
     * with options to view, run, or delete each file.
     * 
     * @return void
     */
    public function index(): void {
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
     * Output all module manifests as raw JSON
     * 
     * Scans the modules directory for manifest.json files in root module folders
     * (excluding child modules) and outputs them as JSON.
     * 
     * This method is publicly accessible in any environment for package
     * management systems to discover modules with manifests.
     * 
     * @return void
     */
    public function manifests(): void {
        $manifests = $this->scan_for_manifests();
        
        // Output as raw JSON
        header('Content-Type: application/json');
        echo json_encode($manifests, JSON_PRETTY_PRINT);
    }
    
    /**
     * Handle AJAX POST requests for SQL file operations
     * 
     * Processes various actions: view SQL, delete file, run SQL, check finish.
     * 
     * @return void
     */
    public function process(): void {
        $data = $this->get_post_data();
        
        if (!isset($data->action)) {
            http_response_code(400);
            die();
        }
        
        $this->route_action($data);
    }
    
    /**
     * Get SQL files from the original module directory
     * 
     * Uses the return URL stored in session to determine which module
     * to scan for SQL files. Handles both parent and child modules.
     * 
     * @return array<string> Array of SQL file paths
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
     * Get the return URL from session storage
     * 
     * Retrieves the URL to redirect back to after SQL file processing.
     * 
     * @return string The return URL or empty string if not set
     */
    private function get_return_url(): string {
        if (isset($_SESSION['tg_return_url'])) {
            return $_SESSION['tg_return_url'];
        }
        
        return '';
    }
    
    /**
     * Extract module directory path from a URL
     * 
     * Example: https://site.com/users/create -> modules/users/
     * Handles both parent modules and child modules (parent-child format).
     * 
     * @param string $url The URL to extract module path from
     * @return string The module directory path or empty string
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
     * 
     * Creates a formatted list of SQL files with appropriate action buttons
     * based on file size and SQL safety checks.
     * 
     * @param array<string> $files Array of SQL file paths
     * @return string HTML representation of the file list
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
     * 
     * Creates a warning button for SQL files that exceed the maximum allowed size.
     * 
     * @param string $filename The name of the SQL file
     * @param string $filepath The full path to the SQL file
     * @return string HTML button element
     */
    private function get_file_too_big_button(string $filename, string $filepath): string {
        $escaped_filename = addslashes($filename);
        $escaped_filepath = addslashes($filepath);
        return '<button class="danger" onclick="explain_too_big(\'' . $escaped_filename . '\', \'' . $escaped_filepath . '\')">TOO BIG!</button>';
    }
    
    /**
     * Get action button based on SQL safety check
     * 
     * Creates either a standard view button or a warning button based on
     * whether the SQL file passes safety checks.
     * 
     * @param string $file The full path to the SQL file
     * @return string HTML button element
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
     * Get and decode POST data from AJAX requests
     * 
     * Reads raw POST data and decodes it as JSON.
     * 
     * @return object Decoded POST data as object
     */
    private function get_post_data(): object {
        $posted_data = file_get_contents('php://input');
        return json_decode($posted_data);
    }
    
    /**
     * Route to appropriate action handler based on POST data
     * 
     * @param object $data Decoded POST data containing action and parameters
     * @return void
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
     * 
     * Outputs the contents of a SQL file for viewing in the browser.
     * 
     * @param object $data POST data containing controllerPath
     * @return void
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
     * 
     * Deletes a SQL file from the module directory.
     * 
     * @param object $data POST data containing targetFile
     * @return void
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
     * 
     * Executes SQL code and deletes the source file upon success.
     * 
     * @param object $data POST data containing sqlCode and targetFile
     * @return void
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
     * 
     * Checks if there are more SQL files to process or if all are done.
     * 
     * @return void
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
     * 
     * Returns the original URL if set in session, otherwise BASE_URL.
     * 
     * @return string The URL to redirect to
     */
    private function get_finish_url(): string {
        $return_url = $this->get_return_url();
        
        if ($return_url !== '') {
            return $return_url;
        }
        
        return BASE_URL;
    }
    
    /**
     * Scan modules directory for manifest.json files in root module folders
     * 
     * Scans all root-level module directories for manifest.json files.
     * Excludes child modules (those with hyphens in their names).
     * Parses JSON and includes error information for invalid files.
     * 
     * @return array<string, mixed> Array of module names and their parsed manifest data
     */
    private function scan_for_manifests(): array {
        $modules_dir = APPPATH . 'modules/';
        $manifests = [];
        
        // Get all directories in modules folder
        $items = scandir($modules_dir);
        
        foreach ($items as $item) {
            // Skip . and .. and non-directories
            if ($item === '.' || $item === '..' || !is_dir($modules_dir . $item)) {
                continue;
            }
            
            // Skip child modules (those containing a hyphen)
            if (strpos($item, '-') !== false) {
                continue;
            }
            
            $manifest_path = $modules_dir . $item . '/manifest.json';
            
            // Check if manifest.json exists
            if (file_exists($manifest_path)) {
                $manifest_content = file_get_contents($manifest_path);
                
                // Try to parse JSON
                $parsed = json_decode($manifest_content, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // JSON is valid
                    $manifests[$item] = $parsed;
                } else {
                    // JSON is invalid - store error message
                    $manifests[$item] = [
                        'error' => 'Invalid JSON: ' . json_last_error_msg(),
                        'raw_content' => $manifest_content
                    ];
                }
            }
        }
        
        return $manifests;
    }
    
    /**
     * Finish processing and redirect
     * 
     * Clears session data and redirects to the finish URL.
     * 
     * @return void
     */
    private function finish_and_redirect(): void {
        $finish_url = $this->get_finish_url();
        
        // Clear session
        unset($_SESSION['tg_return_url']);
        
        // Redirect
        redirect($finish_url);
    }
}