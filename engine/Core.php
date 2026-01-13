<?php
/**
 * Core Framework Dispatcher
 * 
 * The main request router that handles three types of requests:
 * 1. Vendor assets (/vendor/library/file.css) - serves third-party library files
 * 2. Module assets (module_module/js/script.js) - serves assets from module directories
 * 3. Controller requests - standard MVC routing to controllers and methods
 * 
 * Supports complex module structures including parent/child modules (e.g., cars-accessories).
 * Instantiated on every request immediately after framework bootstrap.
 */
class Core {

    protected $current_module = DEFAULT_MODULE;
    protected $current_controller;
    protected $current_method = DEFAULT_METHOD;

    /**
     * Constructor for the Core class.
     * Depending on the URL, serves either vendor assets, controller content, or module assets.
     */
    public function __construct() {
        // Initialize controller name based on module name
        $this->current_controller = ucfirst($this->current_module);

        if (strpos(ASSUMED_URL, '/vendor/')) {
            $this->serve_vendor_asset();
        } elseif (strpos(ASSUMED_URL, MODULE_ASSETS_TRIGGER) === false) {
            $this->serve_controller();
        } else {
            $this->serve_module_asset();
        }
    }

    /**
     * Serve controller class.
     * Optimized for Trongate v2: Handles query params, dev-mode SQL transfers, 
     * and strictly blocks underscore methods from URL access.
     */
    private function serve_controller(): void {
        $segments = SEGMENTS;

        // Parse and sanitize module from segments
        if (isset($segments[1])) {
            $module_with_no_params = explode('?', $segments[1])[0];
            // Security: Ensure module name is only alphanumeric/hyphen/underscore
            $this->current_module = !empty($module_with_no_params) ? preg_replace('/[^a-z0-9-_]/', '', strtolower($module_with_no_params)) : $this->current_module;
            $this->current_controller = ucfirst($this->current_module);
        }

        // Parse and validate method from segments  
        if (isset($segments[2])) {
            $method_with_no_params = explode('?', $segments[2])[0];
            $this->current_method = !empty($method_with_no_params) ? strtolower($method_with_no_params) : $this->current_method;

            // Security: Explicitly block methods starting with _ from URL access
            if (str_starts_with($this->current_method, '_')) {
                $this->draw_error_page();
            }
        }

        $controller_path = $this->get_controller_path();
        require_once $controller_path;

        // Dev environment logic preserved
        if (strtolower(ENV) === 'dev') {
            $this->attempt_sql_transfer($controller_path);
        }

        $this->invoke_controller_method();
    }

    /**
     * Get the correct controller path, handling child modules and 404 fallbacks.
     *
     * @return string The path to the controller file
     */
    private function get_controller_path(): string {
        $controller_path = '../modules/' . $this->current_module . '/' . $this->current_controller . '.php';

        if (file_exists($controller_path)) {
            return $controller_path;
        }

        // Try child controller
        $child_path = $this->try_child_controller();
        if ($child_path !== null) {
            return $child_path;
        }

        // All options exhausted
        $this->draw_error_page();
    }

    /**
     * Attempt to find a child controller.
     *
     * @return string|null The controller path if found, null otherwise
     */
    private function try_child_controller(): ?string {
        $bits = explode('-', $this->current_controller);

        if (count($bits) === 2 && strlen($bits[1]) > 0) {
            $parent_module = strtolower($bits[0]);
            $child_module = strtolower($bits[1]);
            $this->current_controller = ucfirst($bits[1]);
            
            $controller_path = '../modules/' . $parent_module . '/' . $child_module . '/' . ucfirst($bits[1]) . '.php';
            
            if (file_exists($controller_path)) {
                return $controller_path;
            }
        }

        return null;
    }

    /**
     * Draw an error page for a given HTTP response code.
     * Loads the error handler defined in ERROR_404 config.
     *
     * @param int $http_response_code The HTTP response code to send
     * @return void
     */
    private function draw_error_page(int $http_response_code = 404): void {
        http_response_code($http_response_code);

        $handler_parts = explode('/', ERROR_404);
        list($module, $method) = $handler_parts;

        $controller_path = '../modules/' . $module . '/' . ucfirst($module) . '.php';
        $controller_class = ucfirst($module);

        require_once $controller_path;
        $controller = new $controller_class($module);
        $controller->$method();
        die();
    }

    /**
     * Invoke the target controller method.
     * 
     * Instantiates the controller class with the module name and executes the
     * requested method. This implementation prioritizes maximum performance and
     * code clarity by following standard OOP conventions without safety nets.
     * 
     * ARCHITECTURE:
     * Controllers extending Trongate receive the module name via constructor parameter.
     * They must call parent::__construct($module_name) to initialize framework features.
     * 
     * PERFORMANCE:
     * Zero overhead - direct instantiation and method invocation with no validation,
     * reflection, or conditional property checks.
     * 
     * @return void
     */
    private function invoke_controller_method(): void {
        $controller_class = $this->current_controller;
        $controller_instance = new $controller_class($this->current_module);
        
        if (method_exists($controller_instance, $this->current_method)) {
            $controller_instance->{$this->current_method}();
        } else {
            $this->draw_error_page();
        }
    }

    /**
     * Attempt SQL transfer for Module Import Wizard.
     *
     * @param string $controller_path The path to the controller
     * @return void
     */
    private function attempt_sql_transfer(string $controller_path): void {
        $ditch = $this->current_controller . '.php';
        $dir_path = str_replace($ditch, '', $controller_path);

        $files = [];
        foreach (glob($dir_path . "*.sql") as $file) {
            $files[] = $file;
        }

        if (count($files) > 0) {
            $_SESSION['tg_return_url'] = current_url();      // For redirecting back
            $_SESSION['tg_target_module'] = $this->current_module;  // For finding SQL files
            redirect('trongate_control');
        }
    }

    /**
     * Serve vendor assets from the vendor directory.
     *
     * @return void
     */
    private function serve_vendor_asset(): void {
        $vendor_file_path = explode('/vendor/', ASSUMED_URL)[1];
        $vendor_file_path = '../vendor/' . $vendor_file_path;
        
        try {
            $vendor_file_path = $this->sanitize_file_path($vendor_file_path, '../vendor/');
            
            if (file_exists($vendor_file_path)) {
                if (strpos($vendor_file_path, '.css')) {
                    $content_type = 'text/css';
                } else {
                    $content_type = 'text/plain';
                }

                header('Content-type: ' . $content_type);
                $contents = file_get_contents($vendor_file_path);
                echo $contents;
                die();
            } else {
                die('Vendor file not found.');
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Serve module assets with comprehensive security validation.
     *
     * @return void
     */
    private function serve_module_asset(): void {
        $url_segments = SEGMENTS;

        foreach ($url_segments as $url_segment_key => $url_segment_value) {
            $pos = strpos($url_segment_value, MODULE_ASSETS_TRIGGER);

            if (is_numeric($pos)) {
                $target_module = str_replace(MODULE_ASSETS_TRIGGER, '', $url_segment_value);
                $file_name = $url_segments[count($url_segments) - 1];

                // URL Decoding & Initial Validation
                $target_module = urldecode($target_module);
                $file_name = urldecode($file_name);
                
                // Check for null byte injection attacks
                if (strpos($target_module, "\0") !== false || strpos($file_name, "\0") !== false) {
                    http_response_code(400);
                    die('Invalid request');
                }
                
                // Validate module name - only alphanumeric, hyphens, underscores allowed
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $target_module)) {
                    http_response_code(400);
                    die('Invalid module name');
                }
                
                // Validate filename - prevent directory traversal in filename itself
                if (strpos($file_name, '..') !== false || 
                    strpos($file_name, './') !== false || 
                    strpos($file_name, '\\') !== false) {
                    http_response_code(400);
                    die('Invalid file name');
                }

                // Build target directory path with validation
                $target_dir = '';
                for ($i = $url_segment_key + 1; $i < count($url_segments) - 1; $i++) {
                    $segment = urldecode($url_segments[$i]);
                    
                    // Validate each directory segment for traversal attempts
                    if (strpos($segment, '..') !== false || 
                        strpos($segment, "\0") !== false ||
                        strpos($segment, '\\') !== false) {
                        http_response_code(400);
                        die('Invalid path');
                    }
                    
                    $target_dir .= $segment;
                    if ($i < count($url_segments) - 2) {
                        $target_dir .= '/';
                    }
                }

                // Require Subdirectories
                if (empty($target_dir)) {
                    http_response_code(403);
                    die('Access denied: assets must be in subdirectories');
                }

                // Directory Allowlist - Define allowed asset directories
                $allowed_asset_dirs = [
                  'assets',      // catch-all for miscellaneous assets
                  'audio',
                  'css',
                  'documents',
                  'downloads',
                  'files',
                  'fonts',
                  'icons',
                  'images',
                  'img',
                  'javascript',
                  'js',
                  'media',
                  'svg',
                  'uploads',
                  'video'
                ];
                
                // Extract first directory from path
                $dir_parts = explode('/', $target_dir);
                $first_dir = strtolower($dir_parts[0]);
                
                // Validate first directory is in allowlist
                if (!in_array($first_dir, $allowed_asset_dirs, true)) {
                    http_response_code(403);
                    die('Access denied: directory not allowed');
                }

                // Build asset path
                $asset_path = '../modules/' . strtolower($target_module) . '/' . $target_dir . '/' . $file_name;
                
                try {
                    // Sanitize path (handles realpath resolution and traversal)
                    $asset_path = $this->sanitize_file_path($asset_path, '../modules/');
                    
                    if (is_file($asset_path)) {
                        
                        // Post-Sanitization Path Validation
                        $real_modules_path = realpath('../modules/');
                        if (strpos($asset_path, $real_modules_path) !== 0) {
                            http_response_code(403);
                            die('Access denied: path outside modules directory');
                        }
                        
                        // Block symbolic links
                        if (is_link($asset_path)) {
                            http_response_code(403);
                            die('Access denied: symbolic links not allowed');
                        }
                        
                        // File Extension & Type Validation
                        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $file_name_lower = strtolower($file_name);
                        
                        // Require file extension
                        if (empty($file_extension)) {
                            http_response_code(403);
                            die('Access denied: no file extension');
                        }
                        
                        // Comprehensive list of forbidden extensions
                        $forbidden_extensions = [
                            // PHP variants
                            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps',
                            'pht', 'phar', 'inc',
                            // Server-side scripts
                            'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp',
                            // Configuration and sensitive files
                            'sql', 'env', 'ini', 'conf', 'config',
                            // Server configuration
                            'htaccess', 'htpasswd',
                            // Backup/temp files
                            'bak', 'backup', 'old', 'tmp', 'temp', 'swp',
                            // Executables
                            'exe', 'dll', 'so', 'bat', 'cmd', 'com'
                        ];
                        
                        // Block forbidden extensions
                        if (in_array($file_extension, $forbidden_extensions, true)) {
                            http_response_code(403);
                            die('Access denied: forbidden file type');
                        }
                        
                        // Block files with PHP in extension chain (e.g., file.php.txt)
                        if (preg_match('/\.php[^\/]*$/i', $file_name)) {
                            http_response_code(403);
                            die('Access denied: forbidden file type');
                        }
                        
                        // Block specific sensitive filenames
                        $sensitive_files = [
                            'api.json',
                            '.env',
                            '.env.local',
                            '.env.production',
                            '.htaccess',
                            '.htpasswd',
                            'config.php',
                            'database.php',
                            'config.ini',
                            'settings.php',
                            'composer.json',
                            'composer.lock',
                            'package.json',
                            'package-lock.json'
                        ];
                        
                        if (in_array($file_name_lower, $sensitive_files, true)) {
                            http_response_code(403);
                            die('Access denied: sensitive file');
                        }
                        
                        // Block hidden files (starting with .)
                        if (strpos(basename($file_name), '.') === 0) {
                            http_response_code(403);
                            die('Access denied: hidden file');
                        }
                        
                        // Browser Cache Headers
                        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
                            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= filemtime($asset_path)) {
                                header('Last-Modified: '.gmdate('D, d M Y H:i:s',  filemtime($asset_path)).' GMT', true, 304);
                                die;
                        }
                        
                        // MIME Type Detection
                        $content_type = match($file_extension) {
                            // Text-based formats
                            'css' => 'text/css',
                            'js' => 'text/javascript',
                            'json' => 'application/json',
                            'xml' => 'application/xml',
                            'txt' => 'text/plain',
                            'html', 'htm' => 'text/html',
                            'md' => 'text/markdown',
                            
                            // Images
                            'svg' => 'image/svg+xml',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            'ico' => 'image/x-icon',
                            'bmp' => 'image/bmp',
                            'tiff', 'tif' => 'image/tiff',
                            'avif' => 'image/avif',
                            
                            // Fonts
                            'woff' => 'font/woff',
                            'woff2' => 'font/woff2',
                            'ttf' => 'font/ttf',
                            'eot' => 'application/vnd.ms-fontobject',
                            'otf' => 'font/otf',
                            
                            // Audio/Video
                            'mp3' => 'audio/mpeg',
                            'mp4' => 'video/mp4',
                            'webm' => 'video/webm',
                            'ogg' => 'audio/ogg',
                            'wav' => 'audio/wav',
                            'avi' => 'video/x-msvideo',
                            'mov' => 'video/quicktime',
                            
                            // Documents
                            'pdf' => 'application/pdf',
                            'doc' => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'xls' => 'application/vnd.ms-excel',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'ppt' => 'application/vnd.ms-powerpoint',
                            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            
                            // Archives
                            'zip' => 'application/zip',
                            'rar' => 'application/x-rar-compressed',
                            'tar' => 'application/x-tar',
                            'gz' => 'application/gzip',
                            
                            // Fallback to mime_content_type for unknown extensions
                            default => mime_content_type($asset_path)
                        };

                        // Content Type Validation - Block dangerous content types
                        if (strpos(strtolower($content_type), 'php') !== false) {
                            http_response_code(403);
                            die('Access denied: forbidden content type');
                        }

                        // Send Headers and Content
                        header('Content-type: ' . $content_type);
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($asset_path)) . ' GMT');
                        header('X-Frame-Options: SAMEORIGIN');
                        header('X-Content-Type-Options: nosniff');
                        
                        // SVG-specific: Content Security Policy
                        if ($file_extension === 'svg') {
                            header("Content-Security-Policy: script-src 'none'; object-src 'none'; sandbox");
                        }
                        
                        // Send file content
                        readfile($asset_path);
                        die;
                    } 
                } catch (Exception $e) {
                    // Don't expose internal paths in error messages
                    http_response_code(404);
                    die('Asset not found');
                }
            }
        }
    }

    /**
     * Sanitize and validate file paths to prevent directory traversal attacks.
     * 
     * Resolves paths to absolute real paths and ensures they remain within the
     * specified base directory. Supports parent-child module structure resolution
     * (converts 'parent-child' directory names to 'parent/child' paths).
     * 
     * SECURITY:
     * - Prevents directory traversal (../, ..\, etc.)
     * - Validates paths stay within base directory
     * - Handles symbolic links via realpath()
     * 
     * @param string $path Raw file path from URL
     * @param string $base_dir Allowed base directory (e.g., '../modules/')
     * @param bool $is_child_module Internal recursion flag for parent-child resolution
     * @return string Absolute validated real path
     * @throws Exception If path is invalid or outside base directory
     */
    private function sanitize_file_path(string $path, string $base_dir, bool $is_child_module = false): string {
        $real_base_dir = realpath($base_dir);
        $real_path = realpath($path);

        // Security Guard: Check if path exists and is within the allowed base directory
        if ((!$real_path || !str_starts_with($real_path, $real_base_dir)) && !$is_child_module) {
            
            // Priority 2: Standard resolution failed, attempt parent-child translation
            $real_path = $this->sanitize_file_path($path, $base_dir, true);

        } else if ($is_child_module) {

            // Essential v2 logic: Convert 'parent-child' in segment 2 to 'parent/child'
            $path_bits = explode('/', $path);
            if (isset($path_bits[2])) {
                $path_bits[2] = str_replace('-', '/', $path_bits[2]);
                $real_path = realpath(implode('/', $path_bits));
            }

            // Final Security Verification: Ensure the child path is valid and within bounds
            if (!$real_path || !str_starts_with($real_path, $real_base_dir)) {
                http_response_code(404);
                throw new Exception('Invalid file path or directory traversal detected.');
            }
        }

        return $real_path;
    }

}