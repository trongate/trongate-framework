<?php
class Image {
    /** @var resource $image */
    private $image;

    /** @var int $imageType */
    private $imageType;

    /** @var string $fileName */
    private $fileName;

    private $contentType = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    /**
     * @param null $filename
     */
    public function __construct($filename = null) {
        if (extension_loaded('gd')) {
            if ($filename) {
                $this->fileName = $filename;
                $this->load($filename);
            }
        } else {
            echo "<h1 style='color: red';>*** Warning ***</h1>";
            echo "<h2>Trongate requires the GD extension for PHP to be loaded for image uploaders to work</h2>";
            echo "<p>This is not a problem with Trongate but a setup issue with your PHP instance.</p>";
            die("<p>Please open your <i>'php.ini'</i> file and search for <b>'extension=gd'</b> then remove the leading semicolon or add this line, save and restart your web server to enable this change.</p>");
        }
    }

    private function checkFileExists($path) {
        if (!file_exists($path)) {
            throw new NotFoundException("$path does not exist");
        }
    }


    /**
     * @param string $filename
     * @throws NotFoundException
     */
    public function load($filename) {
        $this->checkFileExists($filename);
        $imageInfo = getimagesize($filename);
        $this->imageType = $imageInfo[2];

        if ($this->imageType == IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->imageType == IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($filename);
        } elseif ($this->imageType == IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($filename);
        } elseif ($this->imageType == IMAGETYPE_WEBP) {
            $this->image = imagecreatefromwebp($filename);
        }
    }


    /**
     *  @param string $filename
     *  @param int $compression
     *  @param string $permissions
     */
    public function save($filename = null, $compression = 100, $permissions = null) {
        $filename = ($filename) ?: $this->fileName;

        switch ($this->getImageType()) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $filename, $compression);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image, $filename);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($this->image, $filename);
                break;
            case IMAGETYPE_PNG:
                imagesavealpha($this->image, true);
                imagepng($this->image, $filename);
                break;
        }

        if ($permissions !== null) {
            chmod($filename, (int) $permissions);
        }
    }


    /**
     * @param bool $return either output directly
     * @return null|string image contents  (optional)
     */
    public function output($return = false) {
        $contents = null;
        if ($return) {
            ob_start();
        }
        switch ($this->getImageType()) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($this->image);
                break;
            case IMAGETYPE_PNG:
                imagealphablending($this->image, true);
                imagesavealpha($this->image, true);
                imagepng($this->image);
                break;
        }
        if ($return) {
            $contents = ob_get_flush();
        }
        return $contents;
    }

    /**
     * @return int
     */
    public function getWidth() {
        return imagesx($this->image);
    }

    /**
     * @return int
     */
    public function getHeight() {
        return imagesy($this->image);
    }

    /**
     * @param int $height
     */
    public function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * @param int $width
     */
    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * @param int $scale %
     */
    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getHeight() * $scale / 100;
        $this->resize($width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     */
    public function resizeAndCrop($width, $height) {
        $targetRatio = $width / $height;
        $actualRatio = $this->getWidth() / $this->getHeight();

        if ($targetRatio == $actualRatio) {
            // Scale to size
            $this->resize($width, $height);
        } elseif ($targetRatio > $actualRatio) {
            // Resize to width, crop extra height
            $this->resizeToWidth($width);
            $this->crop($width, $height);
        } else {
            // Resize to height, crop additional width
            $this->resizeToHeight($height);
            $this->crop($width, $height);
        }
    }


    /**
     *  Now with added Transparency resizing feature
     *  @param int $width
     *  @param int $height
     */
    public function resize($width, $height) {
        $newImage = imagecreatetruecolor((int)$width, (int)$height);

        if (($this->getImageType() == IMAGETYPE_GIF) || ($this->getImageType()  == IMAGETYPE_PNG)) {

            // Get transparency color's index number
            $transparency = imagecolortransparent($this->image);

            // Is a strange index other than -1 set?
            if ($transparency >= 0) {

                // deal with alpha channels
                $this->prepWithExistingIndex($newImage, $transparency);
            } elseif ($this->getImageType() == IMAGETYPE_PNG) {

                // deal with alpha channels
                $this->prepTransparentPng($newImage);
            }
        }

        // Now resample the image
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, (int)$width, (int)$height, $this->getWidth(), $this->getHeight());

        // And allocate to $this
        $this->image = $newImage;
    }

    /**
     * @param $resource
     * @param $index
     */
    private function prepWithExistingIndex($resource, $index) {
        // Get the array of RGB vals for the transparency index
        $transparentColor = imagecolorsforindex($this->image, $index);

        // Now allocate the color
        $transparency = imagecolorallocate($resource, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);

        // Fill the background with the color
        imagefill($resource, 0, 0, $transparency);

        // And set that color as the transparent one
        imagecolortransparent($resource, $transparency);
    }

    /**
     * @param $resource
     */
    private function prepTransparentPng($resource) {
        // Set blending mode as false
        imagealphablending($resource, false);

        // Tell it we want to save alpha channel info
        imagesavealpha($resource, true);

        // Set the transparent color
        $color = imagecolorallocatealpha($resource, 0, 0, 0, 127);

        // Fill the image with nothingness
        imagefill($resource, 0, 0, $color);
    }


    /**
     * @param int $width
     * @param int $height
     * @param string $trim
     */
    public function crop($width, $height, $trim = 'center') {
        $offsetX = 0;
        $offsetY = 0;
        $currentWidth = $this->getWidth();
        $currentHeight = $this->getHeight();

        if ($trim != 'left') {
            if ($currentWidth > $width) {
                $diff = $currentWidth - $width;
                $offsetX = ($trim == 'center') ? $diff / 2 : $diff; //full diff for trim right
            }
            if ($currentHeight > $height) {
                $diff = $currentHeight - $height;
                $offsetY = ($trim == 'center') ? $diff / 2 : $diff;
            }
        }

        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $this->image, 0, 0, $offsetX, $offsetY, $width, $height, $width, $height);
        $this->image = $newImage;
    }

    /**
     * @return mixed
     */
    public function getImageType() {
        return $this->imageType;
    }

    /**
     * @return mixed
     * @throws NothingLoadedException
     */
    public function getHeader() {
        if (!$this->imageType) {
            throw new NothingLoadedException();
        }
        return $this->contentType[$this->imageType];
    }

    /**
     *  Frees up memory
     */
    public function destroy() {
        imagedestroy($this->image);
    }

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
        if ($make_rand_name == true) {
            $file_name_without_extension = strtolower(make_rand_str(10));

            //add file extension onto rand file name
            $file_info = return_file_info($target_file['name']);
            $file_extension = $file_info['file_extension'];
            $new_file_name = $file_name_without_extension . $file_extension;
        } else {
            //get the file name and extension
            $file_info = return_file_info($target_file['name']);
            $file_name = $file_info['file_name'];
            $file_extension = $file_info['file_extension'];

            //remove dangerous characters from the file name
            $file_name = url_title($file_name);
            $file_name_without_extension = str_replace('-', '_', $file_name);
            $new_file_name = $file_name_without_extension . $file_extension;
        }

        //set the target destination directory
        if ($upload_to_module == true) {
            $target_module = (isset($data['targetModule']) ? $data['targetModule'] : segment(1));
            $target_destination = '../modules/' . $target_module . '/assets/' . $data['destination'];
        } else {
            $target_destination = '../public/' . $data['destination'];
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

            $data['new_file_path'] = $new_file_path;
            $this->save_that_pic($data);

            //create an array to store file information
            $file_info = array();
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];

            //deal with the thumbnail 
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

    private function save_that_pic($data) {
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

class NothingLoadedException extends Exception {
}

class NotFoundException extends Exception {
}
