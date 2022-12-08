<?php
class Img_helper {

    public function upload($data) {

        if (!isset($data['upload_to_module'])) {
            $data['upload_to_module'] = false;
        }

        //check for valid image width and mime type
        $userfile = array_keys($_FILES)[0];
        $target_file = $_FILES[$userfile];

        $dimension_data = getimagesize($target_file['tmp_name']);
        $image_width = $dimension_data[0];

        if (!is_numeric($image_width)) {
            die('ERROR: non numeric image width');
        }

        $content_type = mime_content_type($target_file['tmp_name']);

        $str = substr($content_type, 0, 6);
        if ($str !== 'image/') {
            die('ERROR: not an image.');
        }

        $tmp_name = $target_file['tmp_name'];
        $data['image'] = new Image($tmp_name);
        $data['tmp_file_width'] = $data['image']->getWidth();
        $data['tmp_file_height'] = $data['image']->getHeight();

        if ($data['upload_to_module'] == true) {
            $target_module = (isset($data['targetModule']) ? $data['targetModule'] : segment(1));
            $data['filename'] = '../modules/'.$target_module.'/assets/'.$data['destination'].'/'.$target_file['name'];
        } else {
            $data['filename'] = '../public/'.$data['destination'].'/'.$target_file['name'];
        }

        if (!isset($data['max_width'])) {
            $data['max_width'] = NULL;
        }

        if (!isset($data['max_height'])) {
            $data['max_height'] = NULL;
        }

        $this->save_that_pic($data);
       
        //rock the thumbnail
        if ((isset($data['thumbnail_max_width'])) && (isset($data['thumbnail_max_height'])) && (isset($data['thumbnail_dir']))) {
            $ditch = $data['destination'];
            $replace = $data['thumbnail_dir'];
            $data['filename'] = str_replace($ditch, $replace, $data['filename']);
            $data['max_width'] = $data['thumbnail_max_width'];
            $data['max_height'] = $data['thumbnail_max_height'];
            $this->save_that_pic($data);
        }
    }

    private function save_that_pic($data) {
        extract($data);
        $reduce_width = false;
        $reduce_height = false;

        if (!isset($data['compression'])) {
            $compression = 100;
        } else {
            $compression = $data['compression'];
        }

        if (!isset($data['permissions'])) {
            $permissions = 775;
        } else {
            $permissions = $data['permissions'];
        }

        //do we need to resize the picture?
        if ((isset($max_width)) && ($tmp_file_width>$max_width)) {
            $reduce_width = true;
            $resize_factor_w = $tmp_file_width / $max_width;
        }

        if ((isset($max_height)) && ($tmp_file_width>$max_height)) {
            $reduce_height = true;
            $resize_factor_h = $tmp_file_height / $max_height;
        }        

        if ((isset($resize_factor_w)) && (isset($resize_factor_h))) {
            if ($resize_factor_w > $resize_factor_h) {
                $reduce_height = false;
            } else {
                $reduce_width = false;
            }
        }

        //either do the height resize or the width resize - never both
        if ($reduce_width == true) {
            $image->resizeToWidth($max_width);
        } elseif($reduce_height == true) {
            $image->resizeToHeight($max_height);
        }

        $image->save($filename, $compression);
    }

}