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
}

class NothingLoadedException extends Exception {
}

class NotFoundException extends Exception {
}
