<?php
class Img_helper {

    public function upload($data) {

        //declare all inbound variables
        $destination = $data['destination'] ?? '';
        $max_width = $data['max_width'] ?? 450;
        $max_height = $data['max_height'] ?? 450;
        $thumbnail_dir = $data['thumbnail_dir'] ?? '';
        $thumbnail_max_width = $data['thumbnail_max_width'] ?? 0;
        $thumbnail_max_height = $data['thumbnail_max_height'] ?? 0;
        $upload_to_module = $data['upload_to_module'] ?? false;
        $make_rand_name = $data['make_rand_name'] ?? false;

        //check for valid image
        $userfile = array_keys($_FILES)[0];
        
        if ($_FILES[$userfile]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("An error occurred while uploading the file. Error code: " . $_FILES[$userfile]['error']);
        }

        $target_file = $_FILES[$userfile];

        $dimension_data = getimagesize($target_file['tmp_name']);
        $image_width = $dimension_data[0];

        if (!is_numeric($image_width)) {
            throw new Exception("ERROR: non numeric image width");
        }

        $tmp_name = $target_file['tmp_name'];
        $data['image'] = new Image($tmp_name);
        $data['tmp_file_width'] = $data['image']->getWidth();
        $data['tmp_file_height'] = $data['image']->getHeight();

        //init $new_file_name variable (the name of the uploaded file)
        if($make_rand_name == true) {
            $file_name_without_extension = strtolower(make_rand_str(10));

            //add file extension onto rand file name
            $file_info = return_file_info($target_file['name']);
            $file_extension = $file_info['file_extension'];
            $new_file_name = $file_name_without_extension.$file_extension;
        } else {
            //get the file name and extension
            $file_info = return_file_info($target_file['name']);
            $file_name = $file_info['file_name'];
            $file_extension = $file_info['file_extension'];

            //remove dangerous characters from the file name
            $file_name = url_title($file_name);
            $file_name_without_extension = str_replace('-', '_', $file_name);
            $new_file_name = $file_name_without_extension.$file_extension;
        }

        //set the target destination directory
        if ($upload_to_module == true) {
            $target_module = (isset($data['targetModule']) ? $data['targetModule'] : segment(1));
            $target_destination = '../modules/'.$target_module.'/assets/'.$data['destination'];
        } else {
            $target_destination = '../public/'.$data['destination'];
        }

        try {
            //make sure the destination folder exists
            if (!is_dir($target_destination)) {
                $error_msg = 'Invalid directory';
                if (strlen($target_destination)>0) {
                    $error_msg.= ': \''.$target_destination.'\' (string '.strlen($target_destination).')';
                }
                throw new Exception($error_msg);
            }

            //upload the temp file to the destination
            $new_file_path = $target_destination.'/'.$new_file_name;

            $i = 2;
            while(file_exists($new_file_path)) {
                $new_file_name = $file_name_without_extension.'_'.$i.$file_extension;
                $new_file_path = $target_destination.'/'.$new_file_name;
                $i++;
            }

            $data['new_file_path'] = $new_file_path;
            $this->save_that_pic($data);
               
            //create an array to store file information
            $file_info = array();
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];

            //deal with the thumbnail 
            if (($thumbnail_max_width>0) && ($thumbnail_max_height>0) && ($thumbnail_dir !== '')) {
                $ditch = $destination;
                $replace = $thumbnail_dir;
                $data['new_file_path'] = str_replace($ditch, $replace, $data['new_file_path']);
                $data['max_width'] = $thumbnail_max_width;
                $data['max_height'] = $thumbnail_max_height;
                $this->save_that_pic($data);
                $file_info['thumbnail_path'] = $data['new_file_path'];
            }

            return $file_info;

        } catch (Exception $e) {
            // Code to handle the exception
            echo "An exception occurred: " . $e->getMessage();
            die();
        }

    }

    private function save_that_pic($data) {
        $new_file_path = $data['new_file_path'] ?? '';
        $compression = $data['compression'] ?? 100;
        $permissions = $data['permissions'] ?? 775;
        $max_width = $data['max_width'] ?? 0;
        $max_height = $data['max_height'] ?? 0;
        $tmp_file_width = $data['tmp_file_width'] ?? 0;
        $tmp_file_height = $data['tmp_file_height'] ?? 0;
        $image = $data['image'];

        if (($max_width>0 && ($tmp_file_width > $max_width)) || ($max_height>0 && ($tmp_file_height > $max_height))) {
            
            //calculate if oversize amount is greater with respect to width or height...
            $resize_factor_w = $tmp_file_width / $max_width;
            $resize_factor_h = $tmp_file_height / $max_height;

            if ($resize_factor_w > $resize_factor_h) {
                $reduce_height = false;
                $reduce_width = true;
            } else {
                $reduce_height = true;
                $reduce_width = false;
            }

            //either do the height resize or the width resize - never both
            if ($reduce_width == true) {
                $image->resizeToWidth($max_width);
            } elseif($reduce_height == true) {
                $image->resizeToHeight($max_height);
            }

        }

        $image->save($new_file_path, $compression);
    }

}