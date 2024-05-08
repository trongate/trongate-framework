<?php

/**
 * Image manipulation class to handle loading, resizing, and saving images.
 * Supports JPEG, GIF, PNG, and WEBP image formats using PHP's GD library.
 * Provides functionalities for loading images, retrieving image metadata,
 * and performing operations such as resizing and cropping.
 *
 * Requires GD extension to be enabled in the PHP configuration.
 */
class Image {

    /**
     * Holds the GD image resource instance.
     * @var resource|null
     */
    private $image;

    /**
     * Stores the type of the image as one of the PHP IMAGETYPE_XXX constants.
     * This type determines which MIME type to use when serving the image via HTTP.
     * @var int|null
     */
    private $image_type;

    /**
     * The file path of the loaded image, used primarily for reference and during saving operations.
     * @var string|null
     */
    private $file_name;

    /**
     * Associative array mapping image types to their respective MIME types.
     * This mapping supports content negotiation when images are served via HTTP.
     * @var array
     */
    private $content_type = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    /**
     * Constructor attempts to load an image if a filename is provided.
     * Throws an error and halts execution if the GD library is not available.
     *
     * @param string|null $filename Path to the image file to load. If null, no image is loaded.
     */
    public function __construct($filename = null) {
        if (!extension_loaded('gd')) {
            echo "<h1 style='color: red;'>*** Warning ***</h1>";
            echo "<h2>Trongate requires the GD extension for PHP to be loaded for image uploaders to work</h2>";
            echo "<p>This is not a problem with Trongate but a setup issue with your PHP instance.</p>";
            die("<p>Please open your <i>'php.ini'</i> file and search for <b>'extension=gd'</b> then remove the leading semicolon or add this line, save and restart your web server to enable this change.</p>");
        }

        if ($filename) {
            $this->file_name = $filename;
            $this->load($filename);
        }
    }

    /**
     * Uploads an image file and handles resizing based on configuration.
     *
     * This method manages the file upload process, checks for errors, and optionally resizes
     * and renames the file. It also supports uploading to specific modules and handling
     * thumbnail creation if specified.
     *
     * @param array $data Configuration data for handling the upload which includes destination path,
     * maximum dimensions, thumbnail settings, and other options.
     * @return array An array containing details about the uploaded file, including new file path,
     * file type, size, and optionally thumbnail path if generated.
     * @throws Exception Throws an exception if an upload error occurs or if specified directories are invalid.
     */
    public function upload(array $data): array {
        $destination = $data['destination'] ?? '';
        $max_width = $data['max_width'] ?? 450;
        $max_height = $data['max_height'] ?? 450;
        $thumbnail_dir = $data['thumbnail_dir'] ?? '';
        $thumbnail_max_width = $data['thumbnail_max_width'] ?? 0;
        $thumbnail_max_height = $data['thumbnail_max_height'] ?? 0;
        $upload_to_module = $data['upload_to_module'] ?? false;
        $make_rand_name = $data['make_rand_name'] ?? false;

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
        $data['tmp_file_width'] = $data['image']->get_width();
        $data['tmp_file_height'] = $data['image']->get_height();

        if ($make_rand_name == true) {
            $file_name_without_extension = strtolower(make_rand_str(10));
            $file_info = return_file_info($target_file['name']);
            $file_extension = $file_info['file_extension'];
            $new_file_name = $file_name_without_extension . $file_extension;
        } else {
            $file_info = return_file_info($target_file['name']);
            $file_name = $file_info['file_name'];
            $file_extension = $file_info['file_extension'];
            $file_name = url_title($file_name);
            $file_name_without_extension = str_replace('-', '_', $file_name);
            $new_file_name = $file_name_without_extension . $file_extension;
        }

        if ($upload_to_module == true) {
            $target_module = (isset($data['targetModule']) ? $data['targetModule'] : segment(1));
            $target_destination = '../modules/' . $target_module . '/assets/' . $data['destination'];
        } else {
            $target_destination = '../public/' . $data['destination'];
        }

        try {
            if (!is_dir($target_destination)) {
                $error_msg = 'Invalid directory';
                if (strlen($target_destination) > 0) {
                    $error_msg .= ': \'' . $target_destination . '\' (string ' . strlen($target_destination) . ')';
                }
                throw new Exception($error_msg);
            }

            $new_file_path = $target_destination . '/' . $new_file_name;
            $i = 2;
            while (file_exists($new_file_path)) {
                $new_file_name = $file_name_without_extension . '_' . $i . $file_extension;
                $new_file_path = $target_destination . '/' . $new_file_name;
                $i++;
            }

            $data['new_file_path'] = $new_file_path;
            $this->save_image($data);

            $file_info = array();
            $file_info['file_name'] = $new_file_name;
            $file_info['file_path'] = $new_file_path;
            $file_info['file_type'] = $target_file['type'];
            $file_info['file_size'] = $target_file['size'];

            if (($thumbnail_max_width > 0) && ($thumbnail_max_height > 0) && ($thumbnail_dir !== '')) {
                $ditch = $destination;
                $replace = $thumbnail_dir;
                $data['new_file_path'] = str_replace($ditch, $replace, $data['new_file_path']);
                $data['max_width'] = $thumbnail_max_width;
                $data['max_height'] = $thumbnail_max_height;
                $this->save_image($data);
                $file_info['thumbnail_path'] = $data['new_file_path'];
            }

            return $file_info;
        } catch (Exception $e) {
            echo "An exception occurred: " . $e->getMessage();
            die();
        }
    }

    /**
     * Loads an image from a given filename and sets the image type based on the file's format.
     *
     * This method first checks if the specified file exists. If it does, it retrieves the image's
     * metadata to determine its type and then loads the image into memory. Supports JPEG, GIF,
     * PNG, and WEBP image formats.
     *
     * @param string $filename The path to the image file to be loaded.
     * @throws Not_found_exception If the file does not exist at the specified path.
     * @return void
     */
    protected function load(string $filename): void {
        $this->check_file_exists($filename);
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];

        switch ($this->image_type) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($filename);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($filename);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($filename);
                break;
            case IMAGETYPE_WEBP:
                $this->image = imagecreatefromwebp($filename);
                break;
            default:
                throw new Exception("Unsupported image type.");
        }
    }

    /**
     * Saves the currently loaded image to a file, with optional compression and file permissions settings.
     *
     * This method saves the image resource held in this class to a specified filename. It handles different
     * image types and applies the specified compression level and file permissions, if provided. If no filename
     * is given, it defaults to the filename stored in the class.
     *
     * @param string|null $filename Optional. The path to save the image file. If null, uses the class's internal filename.
     * @param int $compression Optional. The level of compression (for JPEG and WEBP formats) ranging from 0 (worst quality, smallest file) to 100 (best quality, biggest file). Defaults to 100.
     * @param int|null $permissions Optional. The file permissions to set. If specified, chmod is applied to the file.
     * @return void
     * @throws Exception Throws an exception if there is an error in accessing the image type or writing the file.
     */
    public function save(?string $filename = null, int $compression = 100, ?int $permissions = null): void {
        $filename = $filename ?: $this->file_name;

        switch ($this->get_image_type()) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $filename, $compression);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image, $filename);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($this->image, $filename, $compression);
                break;
            case IMAGETYPE_PNG:
                imagesavealpha($this->image, true);
                imagepng($this->image, $filename);
                break;
            default:
                throw new Exception("Unsupported image type or save operation failed.");
        }

        if ($permissions !== null) {
            chmod($filename, $permissions);
        }
    }

    /**
     * Outputs or returns the image content directly.
     *
     * This method sends the image to the browser or returns the image data as a string based on the parameter provided.
     * It supports different image formats based on the image type determined by get_image_type(). If the `$return` parameter
     * is true, it captures the output using output buffering and returns it as a string. Otherwise, it outputs the image
     * directly to the browser.
     *
     * @param bool $return Determines whether to return the image content as a string (true) or output directly (false).
     * @return string|null Returns image data as a string if `$return` is true; otherwise, nothing is returned.
     */
    public function output(bool $return = false): ?string {
        $contents = null;
        if ($return) {
            ob_start();
        }
        switch ($this->get_image_type()) {
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
            $contents = ob_get_clean();
        }
        return $contents;
    }

    /**
     * Retrieves the width of the loaded image.
     *
     * This method returns the width of the image resource currently loaded in the class.
     * It uses the imagesx() function, which retrieves the width of an image resource.
     *
     * @return int Returns the width of the image in pixels.
     */
    public function get_width(): int {
        return imagesx($this->image);
    }

    /**
     * Retrieves the height of the loaded image.
     *
     * This method returns the height of the image resource currently loaded in the class.
     * It uses the imagesy() function, which retrieves the height of an image resource.
     *
     * @return int Returns the height of the image in pixels.
     */
    public function get_height(): int {
        return imagesy($this->image);
    }

    /**
     * Resizes the image to a specified height while maintaining the aspect ratio.
     *
     * This method calculates the new width of the image that maintains the aspect ratio based on the given height,
     * then resizes the image to these new dimensions.
     *
     * @param int $height The target height to which the image should be resized.
     * @return void
     */
    public function resize_to_height(int $height): void {
        $ratio = $height / $this->get_height();
        $width = $this->get_width() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Resizes the image to a specified width while maintaining the aspect ratio.
     *
     * This method calculates the new height of the image that maintains the aspect ratio based on the given width,
     * then resizes the image to these new dimensions.
     *
     * @param int $width The target width to which the image should be resized.
     * @return void
     */
    public function resize_to_width(int $width): void {
        $ratio = $width / $this->get_width();
        $height = $this->get_height() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Scales the image by a given percentage, adjusting both width and height while maintaining the aspect ratio.
     *
     * This method calculates the new dimensions of the image based on the percentage scale provided.
     * It scales the width and height by the given percentage, then resizes the image to these new dimensions.
     *
     * @param float $scale The percentage by which to scale the image. A value of 100 maintains the original size,
     *                     values less than 100 reduce the size, and values greater than 100 increase the size.
     * @return void
     */
    public function scale(float $scale): void {
        $width = $this->get_width() * $scale / 100;
        $height = $this->get_height() * $scale / 100;
        $this->resize($width, $height);
    }

    /**
     * Resizes and crops the image to specified dimensions.
     *
     * This method adjusts the image to the specified width and height, maintaining the aspect ratio and
     * cropping the excess if necessary. It ensures that the final image matches the target dimensions
     * exactly, even if that involves cropping parts of the image.
     *
     * @param int $width The target width for the image.
     * @param int $height The target height for the image.
     * @return void
     */
    public function resize_and_crop(int $width, int $height): void {
        $target_ratio = $width / $height;
        $actual_ratio = $this->get_width() / $this->get_height();

        if ($target_ratio == $actual_ratio) {
            $this->resize($width, $height);
        } elseif ($target_ratio > $actual_ratio) {
            $this->resize_to_width($width);
            $this->crop($width, $height);
        } else {
            $this->resize_to_height($height);
            $this->crop($width, $height);
        }
    }

    /**
     * Resizes the image to the specified dimensions.
     *
     * This method creates a new image of the given width and height, applies transparency settings if necessary,
     * and resamples the original image onto this new image. This effectively resizes the image to the new dimensions.
     *
     * @param int $width The target width for the resized image.
     * @param int $height The target height for the resized image.
     * @return void
     */
    protected function resize(int $width, int $height): void {
        $new_image = imagecreatetruecolor((int)$width, (int)$height);
        $this->prepare_transparency($new_image);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, (int)$width, (int)$height, $this->get_width(), $this->get_height());
        $this->image = $new_image;
    }

    /**
     * Prepares the transparency settings for a given image resource based on its type.
     *
     * This method configures transparency handling for GIF and PNG images. For GIFs with a specific transparent color,
     * it allocates and sets this color in the new resource. For PNGs, it ensures that alpha blending is disabled and
     * alpha saving is enabled to preserve transparency in the new image.
     *
     * @param resource $resource The image resource to which transparency settings should be applied.
     * @return void
     */
    private function prepare_transparency($resource): void {
        $image_type = $this->get_image_type();
        if ($image_type == IMAGETYPE_GIF || $image_type == IMAGETYPE_PNG) {
            $transparency = imagecolortransparent($this->image);
            if ($transparency >= 0) {
                $transparent_color = imagecolorsforindex($this->image, $transparency);
                $transparency = imagecolorallocate($resource, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($resource, 0, 0, $transparency);
                imagecolortransparent($resource, $transparency);
            } elseif ($image_type == IMAGETYPE_PNG) {
                imagealphablending($resource, false);
                imagesavealpha($resource, true);
                $color = imagecolorallocatealpha($resource, 0, 0, 0, 127);
                imagefill($resource, 0, 0, $color);
            }
        }
    }

    /**
     * Crops the image to specified dimensions from a selected position.
     *
     * This method adjusts the image to the specified width and height. The cropping process
     * can focus on the center or the right side of the image, depending on the `trim` parameter.
     * The resulting image contains the section of the original image resized to the new dimensions.
     *
     * @param int $width The desired width of the cropped image.
     * @param int $height The desired height of the cropped image.
     * @param string $trim Optional. Determines the part of the image to focus on during cropping.
     *                     Can be 'center' or 'right'. Default is 'center'.
     * @return void
     */
    public function crop(int $width, int $height, string $trim = 'center'): void {
        $offset_x = 0;
        $offset_y = 0;
        $current_width = $this->get_width();
        $current_height = $this->get_height();

        if ($trim != 'left') {
            if ($current_width > $width) {
                $diff = $current_width - $width;
                $offset_x = ($trim == 'center') ? $diff / 2 : $diff; // full diff for trim right
            }
            if ($current_height > $height) {
                $diff = $current_height - $height;
                $offset_y = ($trim == 'center') ? $diff / 2 : $diff;
            }
        }

        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height);
        $this->image = $new_image;
    }

    /**
     * Retrieves the type of the currently loaded image.
     *
     * This method returns the image type, typically one of the IMAGETYPE_XXX constants,
     * which corresponds to the format of the image currently managed by this class instance.
     * If no image has been loaded or the type is not determined, null may be returned.
     *
     * @return int|null Returns the image type as one of the predefined IMAGETYPE_XXX constants, or null if not determined.
     */
    public function get_image_type(): ?int {
        return $this->image_type;
    }

    /**
     * Frees up memory allocated to the image resource.
     *
     * This method releases the memory associated with the image resource
     * stored in this class's instance. It is crucial to call this method
     * when the image is no longer needed, especially in scripts that handle
     * multiple or large images to prevent memory leaks.
     *
     * @return void
     */
    public function destroy(): void {
        imagedestroy($this->image);
    }

    /**
     * Saves the processed image to a specified path with configured settings.
     *
     * This method saves an image after optionally resizing it based on maximum width and height constraints.
     * It checks if the actual dimensions exceed the provided maximums and resizes the image to fit within these bounds
     * while maintaining the aspect ratio. Finally, it saves the image to the filesystem with specified compression
     * and permissions.
     *
     * @param array $data Contains all necessary data for saving the image, including:
     *                    - 'new_file_path': the path where the image will be saved.
     *                    - 'compression': the compression level for JPEG/WEBP images.
     *                    - 'permissions': the file permissions to set on the new image.
     *                    - 'max_width': the maximum allowed width for the image.
     *                    - 'max_height': the maximum allowed height for the image.
     *                    - 'tmp_file_width': the temporary file's current width.
     *                    - 'tmp_file_height': the temporary file's current height.
     *                    - 'image': the Image object to be manipulated and saved.
     * @return void
     */
    private function save_image(array $data): void {
        $new_file_path = $data['new_file_path'] ?? '';
        $compression = $data['compression'] ?? 100;
        $permissions = $data['permissions'] ?? 775;
        $max_width = $data['max_width'] ?? 0;
        $max_height = $data['max_height'] ?? 0;
        $tmp_file_width = $data['tmp_file_width'] ?? 0;
        $tmp_file_height = $data['tmp_file_height'] ?? 0;
        $image = $data['image'];

        if (($max_width > 0 && ($tmp_file_width > $max_width)) || ($max_height > 0 && ($tmp_file_height > $max_height))) {
            $resize_factor_w = $tmp_file_width / $max_width;
            $resize_factor_h = $tmp_file_height / $max_height;

            if ($resize_factor_w > $resize_factor_h) {
                $image->resize_to_width($max_width);
            } else {
                $image->resize_to_height($max_height);
            }
        }

        $image->save($new_file_path, $compression, $permissions);
    }

    /**
     * Retrieves the MIME type header of the currently loaded image based on its type.
     *
     * This method returns the appropriate MIME type for the image currently loaded, which
     * is determined by the image type property. It throws an exception if no image type
     * has been set, indicating that no image is loaded.
     *
     * @throws Nothing_loaded_exception If no image has been loaded or the image type is not set.
     * @return string The MIME type of the image, suitable for HTTP Content-Type headers.
     */
    public function get_header(): string {
        if (!$this->image_type) {
            throw new Nothing_loaded_exception();
        }
        return $this->content_type[$this->image_type];
    }

    /**
     * Checks if a file exists at the specified path.
     *
     * This method validates the existence of a file by its path. If the file
     * does not exist, it throws a Not_found_exception to indicate the missing file.
     * The path is used to inform about the specific location of the missing file.
     *
     * @param string $path The file path to check for existence.
     * @throws Not_found_exception If the file does not exist at the specified path.
     * @return void
     */
    private function check_file_exists(string $path): void {
        if (!file_exists($path)) {
            throw new Not_found_exception($path);
        }
    }

}

/**
 * Exception thrown when no image or required data is loaded before attempting an operation.
 */
class Nothing_loaded_exception extends Exception {
    /**
     * Constructs the exception with a default message.
     */
    public function __construct() {
        parent::__construct("No valid data loaded to proceed with the operation.");
    }
}

/**
 * Exception thrown when a specified file or resource cannot be found.
 */
class Not_found_exception extends Exception {
    private $path;

    /**
     * Constructs the exception with a specific message about the missing file or path.
     * @param string $path The path or identifier that was not found.
     */
    public function __construct($path) {
        $this->path = $path;
        parent::__construct("The specified file or resource was not found: $path");
    }

    /**
     * Retrieves the path or identifier that caused the exception.
     * @return string
     */
    public function get_path() {
        return $this->path;
    }
}