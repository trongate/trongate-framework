<?php
class File_helper {

    public function upload($config) {
        extract($config);

        if (!isset($destination)) {
            die('ERROR: upload requires inclusion of \'destination\' property.  Check documentation for details.');
        }

        $userfile = array_keys($_FILES)[0];
        $target_file = $_FILES[$userfile];

        if(!isset($make_rand_name)) {
            $make_rand_name = false;
        }

        $new_file_name = ($make_rand_name === true) ? make_rand_str(10) : $target_file['name'];

        if ($make_rand_name === true) {
            //add file extension onto rand file name
            $bits = explode('.', $target_file['name']);
            $file_extension = '.'.$bits[count($bits)-1];

            $new_file_name = str_replace($file_extension, '', $new_file_name);
            $new_file_name = ltrim(trim(filter_var($new_file_name, FILTER_SANITIZE_STRING)));
            $new_file_name.= $file_extension;
        }

        //make sure the destination folder exists
        $target_destination = '../public/'.$destination;

        if (is_dir($target_destination)) {
            //upload the temp file to the destination
            $new_file_path = $target_destination.'/'.$new_file_name;
            move_uploaded_file($target_file['tmp_name'], $new_file_path);

        } else {
            die('ERROR: Unable to find target file destination: $destination');
        }
    }

}