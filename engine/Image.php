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
     * @var resource|GdImage|null
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
     * This method saves an image resource held in this class to a specified file. It handles different
     * image types and applies the specified compression level for formats that support it (JPEG, WEBP).
     * File permissions can also be set if provided. If no filename is specified, the method uses an
     * internal default filename, which must be set prior to calling this method.
     *
     * @param string|null $filename Optional. The path where the image file will be saved. Defaults to the class's internal filename if null.
     * @param int $compression Optional. Compression level for JPEG and WEBP images, from 0 (worst quality, smallest file) to 100 (best quality, largest file). Defaults to 100.
     * @param int|null $permissions Optional. File permissions to set on the saved file. Uses format (e.g., 0644). If not specified, the system's default permissions are used.
     * @return void
     * @throws InvalidArgumentException If an unsupported image type is encountered or required properties are not set.
     * @throws RuntimeException If writing the file fails.
     */
    protected function save(?string $filename = null, int $compression = 100, ?int $permissions = null): void {
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
                throw new InvalidArgumentException("Unsupported image type or required properties not set.");
        }

        if ($permissions !== null) {
            if (!chmod($filename, $permissions)) {
                throw new RuntimeException("Failed to set file permissions.");
            }
        }
    }

    /**
     * Outputs or returns the image content directly depending on the given parameter.
     *
     * This method handles the output of the image directly to the browser or captures the image data as a string.
     * The operation depends on the `$return` parameter. It utilizes output buffering to capture the output when `$return` is true.
     * Supports various image formats based on the internal image type set within the class.
     *
     * @param bool $return Determines whether to return the image content as a string (true) or to output it directly to the browser (false).
     * @return string|null Returns the image data as a string if `$return` is true; otherwise, outputs directly and returns null.
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
     * Retrieves the width of the currently loaded image.
     *
     * This method returns the width of the image resource that is currently loaded within the instance.
     * It relies on the PHP GD library's imagesx() function to obtain the width of the image resource.
     *
     * @return int The width of the image in pixels, if an image is loaded.
     * @throws Exception If no image is loaded.
     */
    public function get_width(): int {
        if ($this->image === null) {
            throw new Exception("No image is loaded.");
        }
        return imagesx($this->image);
    }

    /**
     * Retrieves the height of the currently loaded image.
     *
     * This method returns the height of the image resource that is currently loaded within the instance.
     * It relies on the PHP GD library's imagesy() function to obtain the height of the image resource.
     *
     * @return int The height of the image in pixels, if an image is loaded.
     * @throws Exception If no image is loaded, ensuring that the method does not fail silently.
     */
    public function get_height(): int {
        if ($this->image === null) {
            throw new Exception("No image is loaded.");
        }
        return imagesy($this->image);
    }

    /**
     * Resizes the image to a specified height while maintaining the aspect ratio.
     *
     * This method calculates the proportional width necessary to maintain the aspect ratio based on a new height,
     * then resizes the image to these new dimensions using the `resize` method.
     *
     * @param int $height The target height to which the image should be resized.
     * @throws Exception If no image is loaded or if the provided height is non-positive.
     * @return void
     */
    public function resize_to_height(int $height): void {
        if ($this->image === null) {
            throw new Exception("No image is loaded to resize.");
        }
        if ($height <= 0) {
            throw new Exception("Height must be greater than zero.");
        }

        $currentHeight = $this->get_height();
        if ($currentHeight === 0) {  // To prevent division by zero
            throw new Exception("Loaded image has zero height, cannot resize.");
        }

        $ratio = $height / $currentHeight;
        $width = $this->get_width() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Resizes the image to a specified width while maintaining the aspect ratio.
     *
     * This method calculates the proportional height necessary to maintain the aspect ratio based on a new width,
     * then resizes the image to these new dimensions using the `resize` method.
     *
     * @param int $width The target width to which the image should be resized.
     * @throws Exception If no image is loaded or if the provided width is non-positive.
     * @return void
     */
    public function resize_to_width(int $width): void {
        if ($this->image === null) {
            throw new Exception("No image is loaded to resize.");
        }
        if ($width <= 0) {
            throw new Exception("Width must be greater than zero.");
        }

        $currentWidth = $this->get_width();
        if ($currentWidth === 0) {  // To prevent division by zero
            throw new Exception("Loaded image has zero width, cannot resize.");
        }

        $ratio = $width / $currentWidth;
        $height = $this->get_height() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Scales the image by a given percentage, adjusting both width and height while maintaining the aspect ratio.
     *
     * This method calculates the new dimensions of the image based on the percentage scale provided.
     * It scales the width and height by the given percentage, then resizes the image to these new dimensions using the `resize` method.
     *
     * @param float $scale The percentage by which to scale the image. A value of 100 maintains the original size,
     *                     values less than 100 reduce the size, and values greater than 100 increase the size.
     * @throws Exception If no image is loaded or if the scale value is not valid.
     * @return void
     */
    public function scale(float $scale): void {
        if ($this->image === null) {
            throw new Exception("No image is loaded to scale.");
        }
        if ($scale <= 0) {
            throw new Exception("Scale must be a positive number.");
        }

        $width = $this->get_width() * $scale / 100;
        $height = $this->get_height() * $scale / 100;
        $this->resize($width, $height);
    }

    /**
     * Resizes and crops the image to specified dimensions.
     *
     * This method adjusts the image to the specified width and height, maintaining the aspect ratio and
     * cropping the excess if necessary. It ensures that the final image matches the target dimensions
     * exactly, even if that involves cropping parts of the image. It handles different aspect ratios by first
     * resizing to the dimension that requires less adjustment before cropping to achieve the desired dimensions.
     *
     * @param int $width The target width for the image.
     * @param int $height The target height for the image.
     * @throws Exception If no image is loaded or if width or height are non-positive values.
     * @return void
     */
    public function resize_and_crop(int $width, int $height): void {
        if ($this->image === null) {
            throw new Exception("No image is loaded to resize and crop.");
        }
        if ($width <= 0 || $height <= 0) {
            throw new Exception("Width and height must be positive integers.");
        }

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
     * Creates a new image resource with the specified width and height, then resamples
     * the current image onto this new canvas. This method maintains image quality and applies
     * appropriate transparency settings based on the image type. As a protected method, it's intended
     * for internal class operations and use by extending classes, supporting common image manipulations
     * such as scaling and cropping that require direct resizing.
     *
     * @param float $width The target width for the resized image, must be a positive integer.
     * @param float $height The target height for the resized image, must be a positive integer.
     * @throws Exception Throws an exception if the dimensions provided are invalid or if no image is loaded.
     * @return void
     */
    protected function resize(float $width, float $height): void {
        if ($this->image === null) {
            throw new Exception("No image is loaded to resize.");
        }
        if ($width <= 0 || $height <= 0) {
            throw new Exception("Width and height must be positive integers.");
        }

        $new_image = imagecreatetruecolor($width, $height);
        if (!$new_image) {
            throw new Exception("Failed to create a new image resource.");
        }

        $image_type = $this->get_image_type();
        if ($image_type === IMAGETYPE_GIF || $image_type === IMAGETYPE_PNG) {
            $this->prepare_transparency($new_image);
        }

        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->get_width(), $this->get_height());
        $this->image = $new_image;
    }

    /**
     * Prepares the transparency settings for a given image resource based on its type.
     *
     * Configures transparency handling for GIF and PNG images. For GIF images with a specific transparent color,
     * allocates this color in the new image resource and sets it as transparent. For PNG images, ensures that
     * alpha blending is disabled and alpha saving is enabled to maintain transparency in the resultant image.
     * This method is critical for preserving the visual integrity of images that require transparency.
     *
     * @param resource|GdImage $resource The image resource to which transparency settings should be applied.
     * @throws Exception Throws an exception if transparency preparation fails.
     * @return void
     */
    private function prepare_transparency($resource): void {
        $image_type = $this->get_image_type();
        if ($image_type === IMAGETYPE_GIF || $image_type === IMAGETYPE_PNG) {
            if ($image_type === IMAGETYPE_GIF) {
                $transparency = imagecolortransparent($this->image);
                if ($transparency >= 0) {
                    $transparent_color = imagecolorsforindex($this->image, $transparency);
                    $transparency = imagecolorallocate($resource, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                    if (!$transparency) {
                        throw new Exception("Failed to allocate transparency color for GIF image.");
                    }
                    imagefill($resource, 0, 0, $transparency);
                    imagecolortransparent($resource, $transparency);
                }
            } elseif ($image_type === IMAGETYPE_PNG) {
                imagealphablending($resource, false);
                imagesavealpha($resource, true);
                $color = imagecolorallocatealpha($resource, 0, 0, 0, 127);
                if (!$color) {
                    throw new Exception("Failed to allocate alpha transparency for PNG image.");
                }
                imagefill($resource, 0, 0, $color);
            }
        } else {
            throw new Exception("Unsupported image type for transparency preparation.");
        }
    }

    /**
     * Crops the image to specified dimensions from a selected position.
     *
     * This method adjusts the image to the specified width and height, focusing the crop based on the `trim` parameter.
     * It will crop the image focusing on the center or the right side, depending on `trim`. If the current image dimensions
     * are less than the desired dimensions, no cropping occurs. The resulting image retains the specified dimensions from
     * the selected part of the original image.
     *
     * @param int $width The desired width of the cropped image.
     * @param int $height The desired height of the cropped image.
     * @param string $trim Optional. Determines the part of the image to focus on during cropping.
     *                     Can be 'center' or 'right'. Default is 'center'. If 'left' is provided, it defaults to no offset.
     * @return void
     * @throws InvalidArgumentException If the given dimensions are invalid or exceed the original dimensions.
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
     * This method returns the image type identified by one of the IMAGETYPE_XXX constants,
     * corresponding to the format of the image currently held by the class instance.
     * The image type is useful for determining how to process or handle the image data.
     * If no image has been loaded or if the type cannot be determined (e.g., the image data is invalid),
     * null is returned. This method is protected to limit its accessibility to within the class itself
     * and its subclasses, supporting encapsulation of the image handling logic.
     *
     * @return int|null Returns the image type as one of the predefined IMAGETYPE_XXX constants.
     *                  Returns null if the image type is not determined or no image is loaded.
     */
    protected function get_image_type(): ?int {
        return $this->image_type;
    }

    /**
     * Frees up memory allocated to the image resource.
     *
     * This method releases the memory associated with the image resource
     * stored in this class's instance. It should be invoked when the image is no longer needed,
     * especially in scripts that process multiple or large images, to prevent memory leaks and manage
     * system resources more efficiently. Failure to call this method in such scenarios can lead to 
     * increased memory usage and possible performance degradation.
     *
     * @return void
     */
    public function destroy(): void {
        if ($this->image !== null) {
            imagedestroy($this->image);
            $this->image = null; // Explicitly unset the image to ensure it's no longer usable.
        }
    }

    /**
     * Saves the processed image to a specified path with configured settings.
     *
     * This method manages the saving of an image after potentially resizing it to meet specified
     * maximum width and height constraints. If the actual dimensions of the image exceed these maximums,
     * the image is resized to fit within the bounds while preserving the aspect ratio. The image is then
     * saved to the filesystem with the specified compression level and file permissions set.
     *
     * @param array $data Configuration array containing all necessary parameters for image processing, including:
     *                    - 'new_file_path': The path where the image will be saved.
     *                    - 'compression': The compression level for JPEG/WEBP images, from 0 (worst quality, smallest file)
     *                      to 100 (best quality, largest file).
     *                    - 'permissions': The file permissions to set on the new image file, e.g., 0644.
     *                    - 'max_width': The maximum allowed width for the image.
     *                    - 'max_height': The maximum allowed height for the image.
     *                    - 'tmp_file_width': The current width of the temporary file.
     *                    - 'tmp_file_height': The current height of the temporary file.
     *                    - 'image': The Image object that is being manipulated and saved.
     * @return void
     * @throws Exception If saving the image fails due to filesystem errors or invalid parameters.
     */
    private function save_image(array $data): void {
        $new_file_path = $data['new_file_path'] ?? '';
        $compression = $data['compression'] ?? 100;
        $permissions = $data['permissions'] ?? 0755; // Default changed to a more common permission setting for images
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
     * This method returns the MIME type corresponding to the image format of the loaded image.
     * It is crucial for setting correct HTTP Content-Type headers when serving images directly
     * from PHP scripts. If the image type has not been set due to the absence of a loaded image,
     * this method throws a Nothing_loaded_exception to prevent misuse and to facilitate debugging.
     *
     * @throws Nothing_loaded_exception If no image has been loaded or if the image type is not set, indicating
     *                                  that operations requiring an image cannot proceed.
     * @return string The MIME type of the image, suitable for HTTP Content-Type headers, e.g., 'image/jpeg'.
     */
    public function get_header(): string {
        if (!$this->image_type) {
            throw new Nothing_loaded_exception("No image has been loaded, or image type is unset.");
        }
        return $this->content_type[$this->image_type];
    }

    /**
     * Validates the existence of a file at the specified path.
     *
     * This method is a utility function within the Image class that checks if a file exists at a given path.
     * It is used internally before performing operations that require the file's presence. If the file does not exist,
     * a Not_found_exception is thrown to handle the error appropriately within the class's workflow.
     *
     * @param string $path The file path to check for existence.
     * @throws Not_found_exception Thrown if the file does not exist at the specified path, indicating an error in file handling.
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
