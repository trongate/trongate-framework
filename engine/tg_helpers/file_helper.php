<?php

/**
 * Helper class for file-related operations.
 */
class File_helper {

    /**
     * Uploads a file based on the provided configuration.
     *
     * This method expects an associative array containing configuration options for the upload process.
     * The $config array should include the following properties:
     * - 'destination' (string): The directory where the file will be uploaded.
     * - 'target_module' (string, optional): The module to which the file will be uploaded. Defaults to the value of segment(1).
     * - 'upload_to_module' (bool, optional): Indicates whether the file should be uploaded to a module directory. Defaults to false.
     * - 'make_rand_name' (bool, optional): Indicates whether to generate a random name for the uploaded file. Defaults to false.
     *
     * @param array $config An associative array containing configuration options for the upload process.
     * @return array An array containing information about the uploaded file (file_name, file_path, file_type, file_size).
     */
    public function upload(array $config): array {

        $destination = $config['destination'] ?? null;
        $target_module = $config['target_module'] ?? segment(1); // Assuming segment() returns a string
        $upload_to_module = $config['upload_to_module'] ?? false;
        $make_rand_name = $config['make_rand_name'] ?? false;

        if (!isset($destination)) {
            die('ERROR: upload requires inclusion of \'destination\' property.  Check documentation for details.');
        }

        $userfile = array_keys($_FILES)[0];
        $target_file = $_FILES[$userfile];

        if (!isset($make_rand_name)) {
            $make_rand_name = false;
        }

        //init $new_file_name variable (the name of the uploaded file)
        if ($make_rand_name == true) {
            $file_name_without_extension = strtolower(make_rand_str(10)); // Assuming make_rand_str() returns a string

            //add file extension onto rand file name
            $file_info = return_file_info($target_file['name']); // Assuming return_file_info() returns an array
            $file_extension = $file_info['file_extension'];
            $new_file_name = $file_name_without_extension . $file_extension;
        } else {
            //get the file name and extension
            $file_info = return_file_info($target_file['name']); // Assuming return_file_info() returns an array
            $file_name = $file_info['file_name'];
            $file_extension = $file_info['file_extension'];

            //remove dangerous characters from the file name
            $file_name = url_title($file_name); // Assuming url_title() returns a string
            $file_name_without_extension = str_replace('-', '_', $file_name);
            $new_file_name = $file_name_without_extension . $file_extension;
        }

        //set the target destination directory
        if ($upload_to_module == true) {
            $target_destination = '../modules/' . $target_module . '/assets/' . $destination;
        } else {
            //add code here to deal with external URLs (AWS, Google Drive, OneDrive, etc...)
            $target_destination = $destination;
        }

        try {
            //make sure the destination folder exists
            if (!is_dir($target_destination)) {
                $error_msg = 'Invalid directory';
                if (strlen($target_destination) > 0) {
                    $error_msg .= ': \'' . $target_destination . '\' (string ' . strlen($target_destination) . ')';
                }
                throw new Exception($error_msg);
            }

            //upload the temp file to the destination
            $new_file_path = $target_destination . '/' . $new_file_name;

            $i = 2;
            while (file_exists($new_file_path)) {
                $new_file_name = $file_name_without_extension . '_' . $i . $file_extension;
                $new_file_path = $target_destination . '/' . $new_file_name;
                $i++;
            }

            move_uploaded_file($target_file['tmp_name'], $new_file_path);

            //create an array to store file information
            $file_info = array();
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];
            return $file_info;
        } catch (Exception $e) {
            // Code to handle the exception
            echo "An exception occurred: " . $e->getMessage();
            die();
        }
    }
}