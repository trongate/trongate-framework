<?php

/**
 * Helper class for image-related operations.
 */
class Img_helper {

    /**
     * Uploads an image file with the specified configuration.
     *
     * @param array $data An array containing upload configuration data.
     *                    Required keys:
     *                      - destination (string): The destination directory path.
     *                      - max_width (int): The maximum width of the image in pixels.
     *                      - max_height (int): The maximum height of the image in pixels.
     *                      - thumbnail_dir (string): The directory path for thumbnails.
     *                      - thumbnail_max_width (int): The maximum width of the thumbnail in pixels.
     *                      - thumbnail_max_height (int): The maximum height of the thumbnail in pixels.
     *                      - upload_to_module (bool): Determines whether to upload to a module directory (true) or public directory (false).
     *                      - make_rand_name (bool): Determines whether to generate a random name for the uploaded file (true) or use the original name (false).
     *                    Optional key:
     *                      - targetModule (string, optional): The target module directory name.
     * @return array An array containing information about the uploaded image file.
     *               Keys:
     *                 - file_name (string): The name of the uploaded file.
     *                 - file_path (string): The full path to the uploaded file.
     *                 - file_type (string): The MIME type of the uploaded file.
     *                 - file_size (int): The size of the uploaded file in bytes.
     *                 - thumbnail_path (string, optional): The path to the generated thumbnail image.
     * @throws Exception If an error occurs during the upload process.
     */
    public function upload(array $data): array {
        // Extracting data from the $data array
        $destination = $data['destination'] ?? '';
        $max_width = $data['max_width'] ?? 450;
        $max_height = $data['max_height'] ?? 450;
        $thumbnail_dir = $data['thumbnail_dir'] ?? '';
        $thumbnail_max_width = $data['thumbnail_max_width'] ?? 0;
        $thumbnail_max_height = $data['thumbnail_max_height'] ?? 0;
        $upload_to_module = $data['upload_to_module'] ?? false;
        $make_rand_name = $data['make_rand_name'] ?? false;

        // Check for a valid image file
        $userfile = array_keys($_FILES)[0];

        // Validate file upload status
        if ($_FILES[$userfile]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("An error occurred while uploading the file. Error code: " . $_FILES[$userfile]['error']);
        }

        $target_file = $_FILES[$userfile];

        // Get dimensions of the uploaded image
        $dimension_data = getimagesize($target_file['tmp_name']);
        $image_width = $dimension_data[0];

        // Ensure the width of the image is numeric
        if (!is_numeric($image_width)) {
            throw new Exception("ERROR: non-numeric image width");
        }

        $tmp_name = $target_file['tmp_name'];
        $data['image'] = new Image($tmp_name);
        $data['tmp_file_width'] = $data['image']->getWidth();
        $data['tmp_file_height'] = $data['image']->getHeight();

        // Initialize $new_file_name variable (the name of the uploaded file)
        if ($make_rand_name == true) {
            // Generate random file name
            $file_name_without_extension = strtolower(make_rand_str(10));

            // Add file extension onto the random file name
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
        if ($upload_to_module == true) {
            $target_module = (isset($data['targetModule']) ? $data['targetModule'] : segment(1));
            $target_destination = '../modules/' . $target_module . '/assets/' . $data['destination'];
        } else {
            $target_destination = '../public/' . $data['destination'];
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

            // Upload the temporary file to the destination
            $new_file_path = $target_destination . '/' . $new_file_name;

            // Rename the file if a file with the same name already exists
            $i = 2;
            while (file_exists($new_file_path)) {
                $new_file_name = $file_name_without_extension . '_' . $i . $file_extension;
                $new_file_path = $target_destination . '/' . $new_file_name;
                $i++;
            }

            $data['new_file_path'] = $new_file_path;
            $this->save_that_pic($data);

            // Create an array to store file information
            $file_info = array();
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];

            // Deal with the thumbnail generation
            if (($thumbnail_max_width > 0) && ($thumbnail_max_height > 0) && ($thumbnail_dir !== '')) {
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

    /**
     * Saves the image file with specified configurations.
     *
     * @param array $data An array containing image data.
     *                    Required keys:
     *                      - new_file_path (string): The path where the image will be saved.
     *                    Optional keys:
     *                      - compression (int, optional): The compression level of the image (0 to 100).
     *                      - permissions (int, optional): The permissions of the saved file.
     *                      - max_width (int, optional): The maximum width of the image in pixels.
     *                      - max_height (int, optional): The maximum height of the image in pixels.
     *                      - tmp_file_width (int, optional): The width of the temporary image file in pixels.
     *                      - tmp_file_height (int, optional): The height of the temporary image file in pixels.
     *                      - image (Image): The image object to be saved.
     * @return void
     */
    private function save_that_pic(array $data): void {
 
        $new_file_path = $data['new_file_path'] ?? '';
        $compression = $data['compression'] ?? 100;
        $permissions = $data['permissions'] ?? 775;
        $max_width = $data['max_width'] ?? 0;
        $max_height = $data['max_height'] ?? 0;
        $tmp_file_width = $data['tmp_file_width'] ?? 0;
        $tmp_file_height = $data['tmp_file_height'] ?? 0;
        $image = $data['image'];

        if (($max_width > 0 && ($tmp_file_width > $max_width)) || ($max_height > 0 && ($tmp_file_height > $max_height))) {

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
            } elseif ($reduce_height == true) {
                $image->resizeToHeight($max_height);
            }
        }

        $image->save($new_file_path, $compression);
    }
}
