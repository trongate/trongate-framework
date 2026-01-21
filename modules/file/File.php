<?php
/**
 * File management class for handling file operations within the application.
 * Supports uploading, reading, writing, deleting, and managing files and directories.
 * 
 * Security restrictions prevent access to critical directories and files under the application root.
 * Advanced validation includes MIME type verification, memory requirements, and path restrictions.
 */
class File {

    /**
     * Class constructor.
     *
     * Prevents direct URL invocation of the module while allowing
     * safe internal usage via application code.
     */
    public function __construct() {
        // Protect this module from unwanted browser access
        block_url('file');
    }

    /**
     * Handles the file upload process with specified configuration.
     *
     * This method validates the upload configuration, processes the uploaded file,
     * performs security checks, generates a secure filename, and moves the file to the
     * target destination. It returns an array containing details about the uploaded file.
     *
     * @param array $config An associative array containing upload configuration options:
     *                      - 'destination': (string) The target directory for the uploaded file.
     *                      - 'target_module': (string) The target module name (defaults to the current segment).
     *                      - 'upload_to_module': (bool) Whether to upload to the module directory (default: false).
     *                      - 'make_rand_name': (bool) Whether to generate a random filename (default: false).
     * @return array An associative array containing details about the uploaded file:
     *               - 'file_name': (string) The name of the uploaded file.
     *               - 'file_path': (string) The full path to the uploaded file.
     *               - 'file_type': (string) The MIME type of the uploaded file.
     *               - 'file_size': (int) The size of the uploaded file in bytes.
     * @throws Exception If the upload fails due to invalid configuration, file upload errors,
     *                   security validation failures, or file movement issues.
     */
    public function upload(array $config): array {
        // Validate basic config
        $destination = $config['destination'] ?? null;
        $target_module = $config['target_module'] ?? segment(1);
        $upload_to_module = $config['upload_to_module'] ?? false;
        $make_rand_name = $config['make_rand_name'] ?? false;

        // Validate upload path
        $this->validate_upload_path($destination, $upload_to_module, $target_module);

        // Get uploaded file
        if (empty($_FILES)) {
            throw new Exception('No file was uploaded');
        }

        $userfile = array_keys($_FILES)[0];
        $upload = $_FILES[$userfile];

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->get_upload_error_message($upload['error']));
        }

        // Security validation
        $this->validate_file($upload['tmp_name']);

        // Generate filename
        $file_info = $this->generate_secure_filename($upload['name'], $make_rand_name);

        // Set target path
        $target_path = $upload_to_module ?
            '../modules/' . $target_module . '/' . $destination :
            $destination;

        // Ensure unique filename
        $final_path = $this->ensure_unique_path($target_path, $file_info['name'], $file_info['extension']);

        // Move file
        if (!move_uploaded_file($upload['tmp_name'], $final_path)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'file_name' => basename($final_path),
            'file_path' => $final_path,
            'file_type' => $upload['type'],
            'file_size' => $upload['size']
        ];
    }

    /**
     * Generate a unique file path by appending incremental numbers if the file already exists.
     * * The first duplicate will be suffixed with '_2', implying the original file is version 1.
     *
     * @param string $directory The target directory path.
     * @param string $base_name The base filename without extension.
     * @param string $extension The file extension including the dot (e.g. '.jpg').
     *
     * @return string The unique file path that does not exist in the directory.
     */
    private function ensure_unique_path(string $directory, string $base_name, string $extension): string {
        $final_path = $directory . '/' . $base_name . $extension;

        // 1. Check if the base file already exists (e.g., greeting.txt)
        if (!file_exists($final_path)) {
            return $final_path;
        }

        // 2. Base file exists, start indexing duplicates from '2' (skipping '_1')
        $counter = 2;
        
        do {
            $final_path = $directory . '/' . $base_name . '_' . $counter . $extension;
            $counter++;
        } while (file_exists($final_path)); // Continue as long as the indexed file exists

        return $final_path;
    }

    /**
     * Retrieves metadata about a file.
     *
     * This method provides information about a file including its size,
     * last modification time, and permissions. Additionally, it now returns the file name
     * and MIME type.
     *
     * @param string $file_path The path to the file.
     * @return array Returns an array with file metadata.
     * @throws Exception if the file does not exist.
     */
    public function info(string $file_path): array {
        if (!file_exists($file_path)) {
            throw new Exception("The file does not exist: $file_path");
        }

        $info = [];
        $info['file_name'] = basename($file_path);  // Get the file name from the path
        $info['size'] = filesize($file_path); // Size in bytes
        $info['modified_time'] = filemtime($file_path); // Last modified time as Unix timestamp
        $info['permissions'] = fileperms($file_path); // File permissions
        $info['mime_type'] = mime_content_type($file_path); // MIME type

        // Format permissions for readability
        $info['readable_permissions'] = substr(sprintf('%o', $info['permissions']), -4);

        // Adding human-readable sizes
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($info['size']) - 1) / 3);
        $info['human_readable_size'] = sprintf("%.2f", $info['size'] / pow(1024, $factor)) . @$sizes[$factor];

        return $info;
    }

    /**
     * Creates a new directory at the specified path.
     *
     * This method allows for the creation of nested directories if they do not exist.
     *
     * @param string $directory_path The path where the directory should be created.
     * @param int $permissions The permissions to set for the directory, in octal notation (e.g., 0755).
     * @param bool $recursive Whether to create nested directories if necessary.
     * @return bool Returns true if the directory was created successfully, or if it already exists.
     * @throws Exception if the directory cannot be created.
     */
    public function create_directory(string $directory_path, int $permissions = 0755, bool $recursive = true): bool {
        if (file_exists($directory_path)) {
            return true;
        }

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($directory_path)) {
            throw new Exception("Access to this path is restricted: $directory_path");
        }

        if (!mkdir($directory_path, $permissions, $recursive)) {
            throw new Exception("Failed to create directory: $directory_path");
        }

        return true;
    }

    /**
     * Checks whether a file or directory exists at the specified path.
     *
     * @param string $path The path to the file or directory.
     * @return bool Returns true if the file or directory exists, otherwise false.
     */
    public function exists(string $path): bool {
        return file_exists($path);
    }

    /**
     * Reads the contents of a file.
     *
     * @param string $file_path The path to the file to be read.
     * @return string Returns the contents of the file.
     * @throws Exception If the file does not exist or cannot be read.
     */
    public function read(string $file_path): string {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($file_path)) {
            throw new Exception("Access to this file is restricted: $file_path");
        }

        if (!file_exists($file_path)) {
            throw new Exception("The file does not exist: $file_path");
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            throw new Exception("Failed to read the file: $file_path");
        }

        return $content;
    }

    /**
     * Writes or appends data to a file.
     *
     * @param string $file_path The path to the file where data should be written.
     * @param mixed $data The data to write to the file.
     * @param bool $append Whether to append data to the file instead of overwriting it.
     * @return bool Returns true on successful write, false on failure.
     * @throws Exception If there is an error writing to the file.
     */
    public function write(string $file_path, $data, bool $append = false): bool {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($file_path)) {
            throw new Exception("Access to this file is restricted: $file_path");
        }

        $flags = $append ? FILE_APPEND : 0;
        $result = file_put_contents($file_path, $data, $flags);
        if ($result === false) {
            throw new Exception("Failed to write to the file: $file_path");
        }

        return true;
    }

    /**
     * Deletes a file from the filesystem.
     *
     * This method checks if the file exists and attempts to delete it. If the file does not exist or cannot be deleted,
     * an exception is thrown.
     *
     * @param string $file_path The path to the file that needs to be deleted.
     * @return bool Returns true if the file is successfully deleted.
     * @throws Exception If the file does not exist or the deletion fails.
     */
    public function delete(string $file_path): bool {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($file_path)) {
            throw new Exception("Access to this file is restricted: $file_path");
        }

        if (!file_exists($file_path)) {
            throw new Exception("The file does not exist: $file_path");
        }

        if (!unlink($file_path)) {
            throw new Exception("Failed to delete the file: $file_path");
        }

        return true;
    }

    /**
     * Initiates a file download or displays inline from the server or an external URL.
     *
     * This method prepares and sends headers based on the parameters to either initiate a file download
     * from the server's local storage or display it inline. It checks if the file exists and is readable before proceeding.
     *
     * @param string $file_path The path or URL of the file.
     * @param bool $as_attachment Determines whether to force the file download (true) or display inline (false).
     * @throws Exception If the file does not exist or cannot be read.
     * @return void
     */
    public function download(string $file_path, bool $as_attachment = true): void {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($file_path)) {
            throw new Exception("Access to this file is restricted: $file_path");
        }

        // Check if the file exists in the given path
        if (!file_exists($file_path)) {
            throw new Exception("The file does not exist: $file_path");
        }

        // Ensure the file is readable
        if (!is_readable($file_path)) {
            throw new Exception("The file is not accessible: $file_path");
        }

        // Clean all buffering to avoid interference with the file
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Determine content disposition based on $as_attachment flag
        $content_disposition = $as_attachment ? 'attachment' : 'inline';

        // Set headers for file download or display
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: ' . $content_disposition . '; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        // Flush system output buffer
        flush();

        // Read the file and send it to the output buffer
        readfile($file_path);

        // Terminate the script to prevent additional output
        exit;
    }

    /**
     * Lists files and directories within a specified directory.
     *
     * This method returns an array containing the names of files and directories
     * within the specified directory. It has an option to perform a recursive listing,
     * which includes all subdirectories and their contents.
     *
     * @param string $directory_path The path to the directory whose contents are to be listed.
     * @param bool $recursive Determines whether the listing should be recursive.
     * @throws Exception If the specified directory does not exist or is not a directory.
     * @return array An array of file and directory names from the specified directory.
     */
    public function list_directory(string $directory_path, bool $recursive = false): array {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($directory_path)) {
            throw new Exception("Access to this path is restricted: $directory_path");
        }

        // Check if the directory exists
        if (!is_dir($directory_path)) {
            throw new Exception("The specified path is not a directory: $directory_path");
        }

        $result = [];
        $items = new DirectoryIterator($directory_path);

        foreach ($items as $item) {
            if ($item->isDot()) {
                continue; // Skip current and parent directory references
            }

            // If the item is a directory and recursive is true, recurse into it
            if ($item->isDir() && $recursive) {
                $subdirectory_items = $this->list_directory($item->getPathname(), true);
                $result[$item->getFilename()] = $subdirectory_items; // Store with directory as key
            } else {
                // Otherwise, add the filename to the result
                $result[] = $item->getFilename();
            }
        }

        return $result;
    }

    /**
     * Copies a file from one location to another.
     *
     * @param string $source_path The path to the source file.
     * @param string $destination_path The path to the destination where the file will be copied.
     * @return bool Returns true on success, or false on failure.
     * @throws Exception if the source file does not exist or the copy fails.
     */
    public function copy(string $source_path, string $destination_path): bool {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($source_path)) {
            throw new Exception("Access to this path is restricted: $source_path");
        }

        if (!file_exists($source_path)) {
            throw new Exception("The source file does not exist: $source_path");
        }

        if (!copy($source_path, $destination_path)) {
            throw new Exception("Failed to copy the file from $source_path to $destination_path");
        }

        return true;
    }

    /**
     * Moves a file from one location to another.
     *
     * @param string $source_path The path to the source file.
     * @param string $destination_path The path to the destination where the file will be moved.
     * @return bool Returns true on success, or false on failure.
     * @throws Exception if the source file does not exist or the move fails.
     */
    public function move(string $source_path, string $destination_path): bool {

        // Validate the path to ensure it's allowed based on predefined security rules
        if (!$this->is_path_valid($source_path)) {
            throw new Exception("Access to this path is restricted: $source_path");
        }

        if (!file_exists($source_path)) {
            throw new Exception("The source file does not exist: $source_path");
        }

        if (!rename($source_path, $destination_path)) {
            throw new Exception("Failed to move the file from $source_path to $destination_path");
        }

        return true;
    }

    /**
    * Validates the upload destination path and ensures it exists and is accessible.
    *
    * @param string $destination The target upload directory path
    * @param bool $upload_to_module Whether to upload to a module directory (default: false)
    * @param string $target_module The target module name if uploading to module (default: '')
    *
    * @throws Exception If:
    *                   - Destination is empty
    *                   - Target path is not a directory
    *                   - Path validation fails for non-module uploads
    *
    * @return void
    */
    private function validate_upload_path(string $destination, bool $upload_to_module = false, string $target_module = ''): void {
        if (empty($destination)) {
            throw new Exception('Upload destination not specified');
        }

        if ($upload_to_module === true) {
            $target_path = '../modules/' . $target_module . '/' . $destination;
        } else {
            $target_path = $destination;
        }

        if (!is_dir($target_path)) {
            throw new Exception('Invalid upload destination');
        }

        // Use existing is_path_valid() as final check if not uploading to module
        if ($upload_to_module === false && !$this->is_path_valid($target_path)) {
            throw new Exception('Unauthorized upload location');
        }
    }

    /**
    * Generates a secure filename for an uploaded file, either randomized or based on original name.
    *
    * @param string $original_name The original filename from the upload
    * @param bool $make_rand_name Whether to generate a random filename (default: false)
    * 
    * @return array{
    *    name: string,           The base filename without extension
    *    extension: string,      The lowercase file extension
    *    full_name: string      The complete filename with extension
    * }
    */

    /**
     * Generates a secure filename for an uploaded file, either randomized or sanitized from original.
     *
     * This method creates a safe filename suitable for filesystem storage by either generating
     * a random name or by sanitizing the original filename using the sanitize_filename() helper.
     * The sanitize_filename() approach provides null byte protection, proper internationalization
     * via transliteration, and consistent filename handling across the framework.
     *
     * When random names are generated, a 10-character lowercase alphanumeric string is created
     * while preserving the original file extension. When using the original name, the entire
     * filename (including extension) is processed through sanitize_filename() for comprehensive
     * security and character handling.
     *
     * @param string $original_name The original filename from the file upload.
     * @param bool $make_rand_name Whether to generate a random filename (true) or sanitize the original (false).
     * 
     * @return array{name: string, extension: string, full_name: string} An associative array containing:
     *               - 'name' (string): The base filename without extension
     *               - 'extension' (string): The lowercase file extension including the dot (e.g., '.jpg')
     *               - 'full_name' (string): The complete filename with extension
     */
    private function generate_secure_filename(string $original_name, bool $make_rand_name): array {
        
        if ($make_rand_name === true) {
            // Generate random filename, preserve original extension
            $file_info = return_file_info($original_name);
            $file_name = strtolower(make_rand_str(10));
            $extension = strtolower($file_info['file_extension']);
        } else {
            // IMPROVED: Use sanitize_filename() helper
            $sanitized = sanitize_filename($original_name);
            
            // Extract the sanitized parts
            $file_info = return_file_info($sanitized);
            $file_name = $file_info['file_name'];
            $extension = $file_info['file_extension']; // Already lowercase
        }
        
        return [
            'name' => $file_name,
            'extension' => $extension,
            'full_name' => $file_name . $extension
        ];
    }

    /**
     * Checks if a given path is valid based on predefined security rules.
     *
     * This method validates a file path to ensure it does not reside in restricted directories,
     * is not directly under the application root, and is within the application's directory scope.
     * The function prevents directory traversal attacks and unauthorized file access by validating
     * against a list of restricted paths and checking the path's relative position to the application's root.
     *
     * If the path doesn't exist yet, it validates the parent directory instead.
     *
     * @param string $path The file or directory path to validate.
     * @return bool Returns true if the path is valid, false otherwise.
     */
    private function is_path_valid(string $path): bool {
        $restricted_dirs = [APPPATH . 'config', APPPATH . 'engine'];
        
        // If the path exists, validate it directly
        if (file_exists($path)) {
            $normalized_path = realpath($path);
            
            // Check if the path is in a restricted directory
            foreach ($restricted_dirs as $dir) {
                $restricted_real_path = realpath($dir);
                if ($restricted_real_path && strpos($normalized_path, $restricted_real_path) === 0) {
                    return false; // Path is inside a restricted directory
                }
            }
            
            // Prevent manipulation of any files or directories directly under APPPATH
            $relative_path = str_replace(realpath(APPPATH), '', $normalized_path);
            if (strpos($relative_path, DIRECTORY_SEPARATOR) === false) {
                return false; // Path is directly under the root directory
            }
            
            // Ensure the path is within the application directory to avoid external access
            if (strpos($normalized_path, realpath(APPPATH)) !== 0) {
                return false;
            }
            
            return true;
        } 
        
        // If the path doesn't exist, validate its parent directory
        else {
            // Get the parent directory path
            $parent_path = dirname($path);
            
            // If parent path doesn't exist either, return false
            if (!file_exists($parent_path)) {
                // We could recursively check parent paths here, but that might introduce
                // security issues. Better to ensure parent directories exist first.
                return false;
            }
            
            // Validate the parent directory
            $parent_normalized_path = realpath($parent_path);
            
            // Check if the parent path is in a restricted directory
            foreach ($restricted_dirs as $dir) {
                $restricted_real_path = realpath($dir);
                if ($restricted_real_path && strpos($parent_normalized_path, $restricted_real_path) === 0) {
                    return false; // Parent path is inside a restricted directory
                }
            }
            
            // Prevent manipulation of any files or directories directly under APPPATH
            $parent_relative_path = str_replace(realpath(APPPATH), '', $parent_normalized_path);
            if (strpos($parent_relative_path, DIRECTORY_SEPARATOR) === false) {
                return false; // Parent path is directly under the root directory
            }
            
            // Ensure the parent path is within the application directory to avoid external access
            if (strpos($parent_normalized_path, realpath(APPPATH)) !== 0) {
                return false;
            }
            
            // The target path inherits validity from its parent
            return true;
        }
    }

    /**
     * Validates an uploaded file by checking its existence, memory requirements, and MIME type.
     *
     * This method ensures that the file exists, has sufficient memory for processing, and has a valid MIME type.
     * If any validation fails, an exception is thrown.
     *
     * @param string $filename The path to the file to be validated.
     * @return void
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException If the file exceeds memory requirements or fails MIME type validation.
     */
    private function validate_file(string $filename): void {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("File not found: $filename");
        }

        // Memory validation for all files
        $memory_validation = $this->validate_memory_requirements($filename);
        if (!$memory_validation['status']) {
            throw new RuntimeException($memory_validation['message']);
        }

        // Validate MIME type
        $this->validate_mime_type($filename);
    }

    /**
     * Validates the MIME type of a file by comparing the results from `finfo` and the `file` command.
     *
     * This method ensures that the MIME type detected by PHP's `finfo` matches the MIME type
     * reported by the system's `file` command. If a mismatch is detected, an exception is thrown.
     *
     * @param string $filename The path to the file to be validated.
     * @return void
     * @throws InvalidArgumentException If a MIME type mismatch is detected.
     */
    private function validate_mime_type(string $filename): void {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filename);
        finfo_close($finfo);

        // Additional MIME validation for Unix systems
        if (DIRECTORY_SEPARATOR !== '\\') {
            $cmd = 'file --brief --mime ' . escapeshellarg($filename) . ' 2>&1';
            if (function_exists('exec')) {
                $native_mime = trim(exec($cmd));

                // Normalize the MIME type by stripping additional metadata
                $native_mime_base = strtok($native_mime, ';'); // Extract the base MIME type
                $mime_type_base = strtok($mime_type, ';'); // Extract the base MIME type

                if ($native_mime_base !== false && $native_mime_base !== $mime_type_base) {
                    throw new InvalidArgumentException('MIME type mismatch detected');
                }
            }
        }
    }

    /**
     * Validates whether there is sufficient memory available to process a file.
     *
     * This method checks if the system has enough memory to handle the file by comparing
     * the file size (with a processing buffer) to the available memory. It returns an array
     * indicating the validation status and an optional error message.
     *
     * @param string $filename The path to the file to be validated.
     * @return array An associative array containing:
     *               - 'status': (bool) Whether there is sufficient memory (true) or not (false).
     *               - 'message': (string) An error message if memory is insufficient (empty string otherwise).
     */
    private function validate_memory_requirements(string $filename): array {
        $result = ['status' => true, 'message' => ''];
        
        if (!function_exists('memory_get_usage')) {
            return $result;
        }

        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return $result;
        }

        // Convert memory limit to bytes
        $memory_limit = $this->convert_to_bytes($memory_limit);
        $file_size = filesize($filename);
        $needed_memory = $file_size * 2.1; // Buffer for processing

        $memory_available = $memory_limit - memory_get_usage();
        
        if ($needed_memory > $memory_available) {
            return [
                'status' => false,
                'message' => 'Insufficient memory to process file'
            ];
        }

        return $result;
    }

    /**
     * Returns a user-friendly error message based on the provided file upload error code.
     *
     * This method maps PHP's file upload error codes to descriptive error messages.
     * It is used to provide meaningful feedback when a file upload fails.
     *
     * @param int $error_code The file upload error code (e.g., UPLOAD_ERR_INI_SIZE).
     * @return string A user-friendly error message corresponding to the error code.
     */
    private function get_upload_error_message(int $error_code): string {
        return match($error_code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }

    /**
     * Converts memory value strings (like '128M', '1G') to bytes
     * 
     * @param string $memory_value The memory value with unit suffix
     * @return int The value in bytes
     */
    private function convert_to_bytes(string $memory_value): int {
        $unit = strtolower(substr($memory_value, -1));
        $value = (int) substr($memory_value, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $memory_value,
        };
    }

}
