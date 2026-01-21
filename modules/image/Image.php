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
     * Constructor for standalone image utility
     * 
     * @param string|null $filename Path to image file to load
     */
    public function __construct(?string $filename = null) {

        // Protect this module from unwanted browser access
        block_url('image');
        
        if (!extension_loaded('gd')) {
            echo "<h1 style='color: red;'>*** Warning ***</h1>";
            echo "<h2>Image processing requires the GD extension for PHP</h2>";
            echo "<p>Please open your <i>'php.ini'</i> file and search for <b>'extension=gd'</b> then remove the leading semicolon or add this line, save and restart your web server.</p>";
            die();
        }
        
        if ($filename) {
            $this->file_name = $filename;
            $this->load($filename);
        }
    }

    /**
     * Handles the upload and processing of an image file.
     *
     * This method manages the entire process of uploading an image file, including:
     * - Validating and moving the uploaded file.
     * - Loading the uploaded image for further processing.
     * - Resizing the image if its dimensions exceed the specified maximum width or height.
     * - Generating a thumbnail if requested.
     *
     * @param array $data Configuration data for handling the upload and processing of the image. Expected keys include:
     *                    - 'destination' (string): The directory where the file will be uploaded.
     *                    - 'max_width' (int, optional): The maximum allowed width for the image (default: 450).
     *                    - 'max_height' (int, optional): The maximum allowed height for the image (default: 450).
     *                    - 'thumbnail_dir' (string, optional): The directory where the thumbnail will be saved (default: '').
     *                    - 'thumbnail_max_width' (int, optional): The maximum width for the thumbnail (default: 0).
     *                    - 'thumbnail_max_height' (int, optional): The maximum height for the thumbnail (default: 0).
     *                    - 'upload_to_module' (bool, optional): Whether to upload the file to a module-specific directory (default: false).
     *                    - 'make_rand_name' (bool, optional): Whether to generate a random name for the uploaded file (default: false).
     *                    - 'target_module' (string, optional): The target module for module-specific uploads (default: segment(1)).
     *
     * @return array An associative array containing details about the uploaded file, including:
     *               - 'file_name' (string): The name of the uploaded file.
     *               - 'file_path' (string): The full path to the uploaded file.
     *               - 'file_type' (string): The MIME type of the uploaded file.
     *               - 'file_size' (int): The size of the uploaded file in bytes.
     *               - 'thumbnail_path' (string, optional): The full path to the generated thumbnail, if applicable.
     *
     * @throws Exception If the file upload fails or if there are issues during image processing.
     */
    public function upload(array $data): array {
        // Extract configuration data
        $destination = $data['destination'] ?? '';
        $max_width = $data['max_width'] ?? 450;
        $max_height = $data['max_height'] ?? 450;
        $thumbnail_dir = $data['thumbnail_dir'] ?? '';
        $thumbnail_max_width = $data['thumbnail_max_width'] ?? 0;
        $thumbnail_max_height = $data['thumbnail_max_height'] ?? 0;
        $upload_to_module = $data['upload_to_module'] ?? false;
        $make_rand_name = $data['make_rand_name'] ?? false;
        $target_module = $data['target_module'] ?? segment(1);

        // VALIDATION: EXACTLY match File module's approach
        // Check if files were uploaded
        if (empty($_FILES)) {
            throw new Exception('No file was uploaded');
        }

        // Use first file in $_FILES array (File module's approach)
        $userfile = array_keys($_FILES)[0];
        $uploaded_file = $_FILES[$userfile];

        // Check upload error
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->get_upload_error_message($uploaded_file['error']));
        }

        // Validate it's an image
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $uploaded_file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception("Invalid file type. Only image files are allowed.");
        }

        // Determine destination directory
        if ($upload_to_module === true) {
            $destination = APPPATH . 'modules/' . $target_module . '/' . ltrim($destination, '/');
        }

        // Create destination directory if it doesn't exist
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                throw new Exception("Failed to create destination directory: {$destination}");
            }
        }

        // Generate file name with duplicate checking
        if ($make_rand_name === true) {
            $extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
            $base_name = uniqid('img_', true);
            $extension_with_dot = '.' . $extension;
            
            // Get unique file path
            $file_path = $this->ensure_unique_path($destination, $base_name, $extension_with_dot);
            $file_name = basename($file_path);
        } else {
            // Use sanitize_filename() for robust cleaning
            $sanitized = sanitize_filename($uploaded_file['name']);
            $file_info_parts = return_file_info($sanitized);
            $base_name = $file_info_parts['file_name'];
            $extension = $file_info_parts['file_extension']; // Already includes dot
            
            // Get unique file path
            $file_path = $this->ensure_unique_path($destination, $base_name, $extension);
            $file_name = basename($file_path);
        }

        // Move uploaded file
        if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            throw new Exception("Failed to move uploaded file to destination.");
        }

        // Build file info array
        $file_info = [
            'file_name' => $file_name,
            'file_path' => $file_path,
            'file_type' => $mime_type,
            'file_size' => $uploaded_file['size']
        ];

        // Load the uploaded image for further processing
        $this->load($file_path);

        // Resize the image if necessary
        if (($max_width > 0 && $this->get_width() > $max_width) || ($max_height > 0 && $this->get_height() > $max_height)) {
            $resize_factor_w = $this->get_width() / $max_width;
            $resize_factor_h = $this->get_height() / $max_height;

            if ($resize_factor_w > $resize_factor_h) {
                $this->resize_to_width($max_width);
            } else {
                $this->resize_to_height($max_height);
            }

            // Save the resized image
            $this->save($file_path);
        }

        // Generate a thumbnail if requested
        if ($thumbnail_max_width > 0 && $thumbnail_max_height > 0 && $thumbnail_dir !== '') {
            // Determine thumbnail directory
            if ($upload_to_module === true) {
                $thumbnail_base_dir = APPPATH . 'modules/' . $target_module . '/' . ltrim($thumbnail_dir, '/');
            } else {
                $thumbnail_base_dir = rtrim($thumbnail_dir, '/');
            }
            
            // Create thumbnail directory if it doesn't exist
            if (!is_dir($thumbnail_base_dir)) {
                if (!mkdir($thumbnail_base_dir, 0755, true)) {
                    throw new Exception("Failed to create thumbnail directory: {$thumbnail_base_dir}");
                }
            }
            
            // Extract filename parts for thumbnail
            $thumbnail_file_info = return_file_info($file_name);
            $thumbnail_base_name = $thumbnail_file_info['file_name'];
            $thumbnail_extension = $thumbnail_file_info['file_extension'];
            
            // Get unique thumbnail path (might need different naming if same dir as main)
            if ($thumbnail_base_dir === $destination) {
                // If thumbnail is in same directory as main image, use different base name
                $thumbnail_base_name = 'thumb_' . $thumbnail_base_name;
            }
            
            $thumbnail_path = $this->ensure_unique_path($thumbnail_base_dir, $thumbnail_base_name, $thumbnail_extension);
            
            $thumbnail_data = [
                'new_file_path' => $thumbnail_path,
                'max_width' => $thumbnail_max_width,
                'max_height' => $thumbnail_max_height,
                'tmp_file_width' => $this->get_width(),
                'tmp_file_height' => $this->get_height(),
                'image' => $this,
            ];
            $this->save_image($thumbnail_data);
            $file_info['thumbnail_path'] = $thumbnail_path;
        }

        return $file_info;
    }

    /**
     * Returns a user-friendly error message based on the provided file upload error code.
     *
     * This method maps PHP's file upload error codes to descriptive error messages.
     * It is used to provide meaningful feedback when a file upload fails.
     *
     * @param int $error_code The file upload error code (e.g., UPLOAD_ERR_INI_SIZE).
     * @return string A user-friendly error message corresponding to the error code.
     */
    private function get_upload_error_message(int $error_code): string {
        return match($error_code) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }

    /**
     * Generate a unique file path by appending incremental numbers if the file already exists.
     *
     * @param string $directory The target directory path.
     * @param string $base_name The base filename without extension.
     * @param string $extension The file extension including the dot (e.g. '.jpg').
     *
     * @return string The unique file path that does not exist in the directory.
     */
    private function ensure_unique_path(string $directory, string $base_name, string $extension): string {
        $final_path = $directory . '/' . $base_name . $extension;

        // 1. Check if the base file already exists (e.g., car.jpg)
        if (!file_exists($final_path)) {
            return $final_path;
        }

        // 2. Base file exists, start indexing duplicates from '2'
        $counter = 2;
        
        do {
            $final_path = $directory . '/' . $base_name . '_' . $counter . $extension;
            $counter++;
        } while (file_exists($final_path));

        return $final_path;
    }

    /**
    * Loads and validates an image file into memory.
    *
    * This method performs several steps:
    * 1. Validates the image file for type, size and memory requirements
    * 2. Determines the image type (JPEG, GIF, PNG, WEBP)
    * 3. Creates an appropriate GD image resource based on the type
    * 
    * @param string $filename Path to the image file to be loaded
    * @throws InvalidArgumentException If the image type is unsupported
    * @throws RuntimeException If WebP support is unavailable or if image resource creation fails
    * @return void
    */
    public function load(string $filename): void {

        // Validate file before any operations
        $this->validate_image($filename);

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
                if (!function_exists('imagecreatefromwebp')) {
                    throw new RuntimeException('WebP support not available in this PHP installation');
                }
                $this->image = imagecreatefromwebp($filename);
                break;
            default:
                throw new InvalidArgumentException("Unsupported image type");
        }

        if ($this->image === false) {
            throw new RuntimeException("Failed to create image resource");
        }

    }

    /**
    * Performs comprehensive validation of an image file.
    * 
    * This method executes multiple validation checks on an image file including:
    * - File existence verification
    * - MIME type validation using finfo and getimagesize
    * - File signature/magic number verification
    * - Memory requirement calculations
    *
    * @param string $filename The path to the image file to validate
    * @throws InvalidArgumentException If the file doesn't exist, has invalid MIME type, or invalid signature
    * @throws RuntimeException If image info can't be read or memory requirements exceed limits
    * @return void
    */
    private function validate_image(string $filename): void {

        if (!file_exists($filename)) {
            throw new InvalidArgumentException("File not found: $filename");
        }

        // Enhanced MIME validation with double-checking
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filename);
        finfo_close($finfo);

        $allowed_types = array_values($this->content_type);
        if (!in_array($mime_type, $allowed_types)) {
            throw new InvalidArgumentException('Invalid image type');
        }

        // Additional image validation
        $image_info = @getimagesize($filename);
        if ($image_info === false) {
            throw new RuntimeException('Failed to get image information');
        }

        $detected_mime = $image_info['mime'];

        // Verify MIME type matches getimagesize result
        $detected_mime_base = strtolower(substr($detected_mime, 0, strpos($detected_mime, '/')));
        $mime_type_base = strtolower(substr($mime_type, 0, strpos($mime_type, '/')));
        if ($detected_mime_base !== 'image' || $mime_type_base !== 'image') {
            throw new InvalidArgumentException('MIME type mismatch detected');
        }

        // Validate file signatures
        $file_content = file_get_contents($filename, false, null, 0, 8);
        $signatures = [
            IMAGETYPE_JPEG => ["\xFF\xD8\xFF"],
            IMAGETYPE_PNG => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            IMAGETYPE_GIF => ["GIF87a", "GIF89a"],
            IMAGETYPE_WEBP => ["RIFF", "WEBP"]
        ];

        $valid_signature = false;
        foreach ($signatures[$image_info[2]] ?? [] as $sig) {
            if (strpos($file_content, $sig) === 0) {
                $valid_signature = true;
                break;
            }
        }
        if (!$valid_signature) {
            throw new InvalidArgumentException('Invalid file signature');
        }

        if (($file = @fopen($filename, 'rb')) === FALSE) {
            throw new RuntimeException('Unable to analyze image file');
        }
        $opening_bytes = fread($file, 256);
        fclose($file);

        // Enhanced pattern including PHP tags
        if (preg_match('/<(script|iframe|object|embed|applet)[\s>]|<\?php|<\?/i', $opening_bytes)) {
            throw new InvalidArgumentException('Potential security threat detected in image');
        }

        $required_memory = $image_info[0] * $image_info[1] * 4 * 1.5;
        if (memory_get_usage() + $required_memory > $this->get_memory_limit()) {
            throw new RuntimeException('Image too large to process');
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

        if ($target_ratio === $actual_ratio) {
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
                $offset_x = ($trim === 'center') ? $diff / 2 : $diff; // full diff for trim right
            }
            if ($current_height > $height) {
                $diff = $current_height - $height;
                $offset_y = ($trim === 'center') ? $diff / 2 : $diff;
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
    * Gets the PHP memory limit in bytes.
    * 
    * Retrieves and converts the PHP memory_limit configuration value to bytes.
    * Handles limit values specified with K (Kilobytes), M (Megabytes), or G (Gigabytes) suffixes.
    * Returns PHP_INT_MAX if memory_limit is set to -1 (unlimited).
    *
    * Example values handled:
    * - "128M" -> 134217728 (bytes)
    * - "1G" -> 1073741824 (bytes)
    * - "-1" -> PHP_INT_MAX
    *
    * @return int Memory limit in bytes
    */
    private function get_memory_limit(): int {
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int)substr($memory_limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
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
    * Gets the MIME type header for the currently loaded image.
    * 
    * Returns the appropriate MIME type (e.g., 'image/jpeg', 'image/png') for the loaded image.
    * This MIME type can be used in HTTP Content-Type headers when serving the image.
    *
    * @throws InvalidArgumentException If no image has been loaded or image type is not set
    * @return string The MIME type of the current image (e.g., 'image/jpeg', 'image/png', 'image/gif', 'image/webp')
    */
    public function get_header(): string {
        if (!$this->image_type) {
            throw new InvalidArgumentException("No image has been loaded, or image type is unset.");
        }
        return $this->content_type[$this->image_type];
    }

    /**
    * Validates that a file exists at the specified path.
    * 
    * A utility method that checks if a file exists at the given path.
    * Used for basic file existence validation before attempting file operations.
    *
    * @param string $path The filesystem path to check
    * @throws InvalidArgumentException If no file exists at the specified path
    * @return void
    */
    private function check_file_exists(string $path): void {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File not found: $path");
        }
    }

}