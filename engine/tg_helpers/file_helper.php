<?php
class File_helper {

    public function upload($config) {
        extract($config);

        if (!isset($destination)) {
            die('ERROR: upload requires inclusion of \'destination\' property.  Check documentation for details.');
        }

        //make sure the destination folder exists
        if (!isset($upload_root)) {
            $upload_root = '../public/';
        }
        $target_destination = $upload_root . $destination;
        if (!is_dir($target_destination)) {
            die('ERROR: Unable to find target file destination: ' . $target_destination);
        }

        //select the desired uploaded file
        if (!isset($userfile)) {
            $userfile = array_keys($_FILES)[0];
        }
        $target_file = $_FILES[$userfile];

        //get file extension
        $bits = explode('.', $target_file['name']);
        $file_extension = '.' . $bits[count($bits) - 1];

        // use given filename or create a new unique filename
        if (!isset($new_file_name) || $new_file_name === false) {
            $new_file_name = basename($target_file['name']);
            $new_file_name = str_replace($file_extension, '', $new_file_name);
            $new_file_name = trim(htmlspecialchars($new_file_name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401));
            $new_file_name .= $file_extension;
            $new_file_path = $target_destination . '/' . $new_file_name;
        } elseif ($new_file_name === true) {
            do {
                // use md5 to get strings with the same length
                $randomString = md5(uniqid($target_file['tmp_name'], true));
                $new_file_name = $randomString . $file_extension;
                $new_file_path = $target_destination . '/' . $new_file_name;
            } while (file_exists($new_file_path));
        }

        move_uploaded_file($target_file['tmp_name'], $new_file_path);
        return $new_file_name;
    }

}
