<?php

declare(strict_types=1);

class Image
{
    /** @var resource */
    private $image;

    private int $imageType;

    private string $fileName;

    private $contentType = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_PNG => 'image/png',
    ];

    public function __construct(null $filename = null)
    {
        if (extension_loaded('gd')) {
            if ($filename) {
                $this->fileName = $filename;
                $this->load($filename);
            }
        } else {
            echo "<h1 style='color: red';>*** Warning ***</h1>";
            echo '<h2>Trongate requires the GD extension for PHP to be loaded for image uploaders to work</h2>';
            echo '<p>This is not a problem with Trongate but a setup issue with your PHP instance.</p>';
            exit("<p>Please open your <i>'php.ini'</i> file and search for <b>'extension=gd'</b> then remove the leading semicolon or add this line, save and restart your web server to enable this change.</p>");
        }
    }

    /**
     * @throws NotFoundException
     */
    public function load(string $filename): void
    {
        $this->checkFileExists($filename);
        $imageInfo = getimagesize($filename);
        $this->imageType = $imageInfo[2];

        if ($this->imageType === IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->imageType === IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($filename);
        } elseif ($this->imageType === IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($filename);
        }
    }

    public function save(?string $filename = null, int $compression = 100, ?string $permissions = null): void
    {
        $filename = $filename ? $filename : $this->fileName;

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
     * @param  bool  $return either output directly
     *
     * @return string|null image contents  (optional)
     */
    public function output(bool $return = false): ?string
    {
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

    public function getWidth(): int
    {
        return imagesx($this->image);
    }

    public function getHeight(): int
    {
        return imagesy($this->image);
    }

    public function resizeToHeight(int $height): void
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    public function resizeToWidth(int $width): void
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * @param  int  $scale %
     */
    public function scale(int $scale): void
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getHeight() * $scale / 100;
        $this->resize($width, $height);
    }

    public function resizeAndCrop(int $width, int $height): void
    {
        $targetRatio = $width / $height;
        $actualRatio = $this->getWidth() / $this->getHeight();

        if ($targetRatio === $actualRatio) {
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
     */
    public function resize(int $width, int $height): void
    {
        $newImage = imagecreatetruecolor((int) $width, (int) $height);

        if (($this->getImageType() === IMAGETYPE_GIF) || ($this->getImageType() === IMAGETYPE_PNG)) {
            // Get transparency color's index number
            $transparency = imagecolortransparent($this->image);

            // Is a strange index other than -1 set?
            if ($transparency >= 0) {
                // deal with alpha channels
                $this->prepWithExistingIndex($newImage, $transparency);
            } elseif ($this->getImageType() === IMAGETYPE_PNG) {
                // deal with alpha channels
                $this->prepTransparentPng($newImage);
            }
        }

        // Now resample the image
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, (int) $width, (int) $height, $this->getWidth(), $this->getHeight());

        // And allocate to $this
        $this->image = $newImage;
    }

    public function crop(int $width, int $height, string $trim = 'center'): void
    {
        $offsetX = 0;
        $offsetY = 0;
        $currentWidth = $this->getWidth();
        $currentHeight = $this->getHeight();

        if ($trim !== 'left') {
            if ($currentWidth > $width) {
                $diff = $currentWidth - $width;
                $offsetX = $trim === 'center' ? $diff / 2 : $diff; //full diff for trim right
            }
            if ($currentHeight > $height) {
                $diff = $currentHeight - $height;
                $offsetY = $trim === 'center' ? $diff / 2 : $diff;
            }
        }

        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $this->image, 0, 0, $offsetX, $offsetY, $width, $height, $width, $height);
        $this->image = $newImage;
    }

    public function getImageType(): mixed
    {
        return $this->imageType;
    }

    /**
     * @throws NothingLoadedException
     */
    public function getHeader(): mixed
    {
        if (! $this->imageType) {
            throw new NothingLoadedException();
        }

        return $this->contentType[$this->imageType];
    }

    /**
     *  Frees up memory
     */
    public function destroy(): void
    {
        imagedestroy($this->image);
    }

    private function checkFileExists($path): void
    {
        if (! file_exists($path)) {
            throw new NotFoundException("{$path} does not exist");
        }
    }

    private function prepWithExistingIndex($resource, $index): void
    {
        // Get the array of RGB vals for the transparency index
        $transparentColor = imagecolorsforindex($this->image, $index);

        // Now allocate the color
        $transparency = imagecolorallocate($resource, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);

        // Fill the background with the color
        imagefill($resource, 0, 0, $transparency);

        // And set that color as the transparent one
        imagecolortransparent($resource, $transparency);
    }

    private function prepTransparentPng($resource): void
    {
        // Set blending mode as false
        imagealphablending($resource, false);

        // Tell it we want to save alpha channel info
        imagesavealpha($resource, true);

        // Set the transparent color
        $color = imagecolorallocatealpha($resource, 0, 0, 0, 127);

        // Fill the image with nothingness
        imagefill($resource, 0, 0, $color);
    }
}

class NothingLoadedException extends Exception
{
}

class NotFoundException extends Exception
{
}
