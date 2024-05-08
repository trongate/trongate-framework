<?php

/**
 * File management class for handling file operations within the application.
 *
 * This class provides a comprehensive suite of methods designed for efficient and secure file management.
 * It supports functionalities like uploading, deleting, reading, writing files, and managing directories.
 * Access restrictions are in place to prevent reading and manipulation of critical directories such as 
 * 'config', 'engine', and 'templates', as well as any files directly under the root application level (e.g., '.htaccess').
 *
 * Users of this class are advised to be aware of the security implications associated with file handling in a web environment,
 * particularly to ensure that operations do not inadvertently expose sensitive application areas.
 */
class File {

    /**
     * Handles the file upload process.
     *
     * This method manages the file upload process including directory validation, file renaming, and moving the file
     * to its final destination. It allows for optional random renaming of files and supports uploading directly to module-specific directories.
     *
     * @param array $config Configuration options for the file upload process. Includes destination directory, module settings, and renaming options.
     * @return array Returns an array with file upload details including file name, path, type, and size.
     * @throws Exception If the directory is invalid or if file movement fails.
     */
    public function upload(array $config): array {

        // Declare all inbound variables
        $destination = $config['destination'] ?? null;
        $target_module = $config['target_module'] ?? segment(1);
        $upload_to_module = $config['upload_to_module'] ?? false;
        $make_rand_name = $config['make_rand_name'] ?? false;

        if (!isset($destination)) {
            die('ERROR: upload requires inclusion of \'destination\' property. Check documentation for details.');
        }

        $userfile = array_keys($_FILES)[0];
        $target_file = $_FILES[$userfile];

        // Initialize the new file name variable (the name of the uploaded file)
        if ($make_rand_name === true) {
            $file_name_without_extension = strtolower(make_rand_str(10));

            // Add file extension onto random file name
            $file_info = return_file_info($target_file['name']);
            $file_extension = $file_info['file_extension'];
            $new_file_name = $file_name_without_extension . $file_extension;
        } else {
            // Get the file name and extension
            $file_info = return_file_info($target_file['name']);
            $file_name = $file_info['file_name'];
            $file_extension = $file_info['file_extension'];

            // Remove dangerous characters from the file name
            $file_name = url_title($file_name);
            $file_name_without_extension = str_replace('-', '_', $file_name);
            $new_file_name = $file_name_without_extension . $file_extension;
        }

        // Set the target destination directory
        if ($upload_to_module === true) {
            $target_destination = '../modules/' . $target_module . '/assets/' . $destination;
        } else {
            // Code here to deal with external URLs (AWS, Google Drive, OneDrive, etc...)
            $target_destination = $destination;
        }

        try {
            // Make sure the destination folder exists
            if (!is_dir($target_destination)) {
                $error_msg = 'Invalid directory';
                if (strlen($target_destination) > 0) {
                    $error_msg .= ': \'' . $target_destination . '\' (string ' . strlen($target_destination) . ')';
                }
                throw new Exception($error_msg);
            }

            // Upload the temp file to the destination
            $new_file_path = $target_destination . '/' . $new_file_name;
            $i = 2;
            while (file_exists($new_file_path)) {
                $new_file_name = $file_name_without_extension . '_' . $i . $file_extension;
                $new_file_path = $target_destination . '/' . $new_file_name;
                $i++;
            }

            if (!move_uploaded_file($target_file['tmp_name'], $new_file_path)) {
                throw new Exception("Failed to move uploaded file to $new_file_path");
            }

            // Create an array to store file information
            $file_info = [];
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];
            return $file_info;
        } catch (Exception $e) {
            echo "An exception occurred: " . $e->getMessage();
            die();
        }
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
        if (!$this->is_path_valid($directory_path)) {
            throw new Exception("Access to this path is restricted: $directory_path");
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
     * Checks if a given path is valid based on predefined security rules.
     *
     * This method validates a file path to ensure it does not reside in restricted directories,
     * is not directly under the application root, and is within the application's directory scope.
     * The function prevents directory traversal attacks and unauthorized file access by validating
     * against a list of restricted paths and checking the path's relative position to the application's root.
     *
     * @param string $path The file or directory path to validate.
     * @return bool Returns true if the path is valid, false otherwise.
     */
    private function is_path_valid(string $path): bool {
        $restricted_dirs = [APPPATH . 'config', APPPATH . 'engine', APPPATH . 'templates'];
        $normalized_path = realpath($path);

        // Check if the path is in a restricted directory
        foreach ($restricted_dirs as $dir) {
            if (strpos($normalized_path, $dir) === 0) {
                return false; // Path is inside a restricted directory
            }
        }

        // Prevent manipulation of any files or directories directly under APPPATH
        $relative_path = str_replace(APPPATH, '', $normalized_path);
        if (strpos($relative_path, DIRECTORY_SEPARATOR) === false) {
            return false; // Path is directly under the root directory
        }

        // Ensure the path is within the application directory to avoid external access
        if (strpos($normalized_path, APPPATH) !== 0) {
            return false;
        }

        return true;
    }

}