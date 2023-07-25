<?php
class Trongate_pages extends Trongate {

    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100);
    private $page_template = 'public';
    private $admin_template = 'admin';
    private $max_file_size_mb = 5; // maximum allowed file size (for images) in megabytes
    private $max_width = 4200; // maximum allowed width for image uploads
    private $max_height = 3200; // set maximum allowed height for image uploads
    private $sample_text = 'Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis. Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit.';

    /**
     * Redirect to manage page if user visits trongate_pages/
     *
     * @return void
     */
    function index(): void {
        redirect('trongate_pages/manage');
    }

    /**
     * Ensures that the current user is allowed to access the protected resource.
     * Feel free to change to suit your own individual use case.
     *
     * @return string|false The security token if the user is authorized, or false otherwise.
     */
    function _make_sure_allowed(): string|false {
        //by default, 'admin' users (i.e., users with user_level_id === 1) are allowed
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        return $token;
    }    

    /**
     * Executes a before hook that updates the input data and sets the 'last_updated' parameter to the current time.
     *
     * @param array $input The input data to be updated.
     * @return array The updated input data.
     */
    function _pre_update(array $input): array {
        $input['params']['last_updated'] = time();
        return $input;
    }

    /**
     * Fetch the uploaded images.
     *
     * @return void
     */
    function fetch_uploaded_images(): void {
        api_auth();
        $directory_name = 'modules/trongate_pages/assets/images/uploads';
        $images = $this->_get_images_in_directory($directory_name);
        http_response_code(200);
        echo json_encode($images);
    }

    /**
     * Returns an array of images within a given directory
     * @param string $directory_name
     *
     * @return array
     */
    function _get_images_in_directory(string $directory_name): array {
        $directory_path = APPPATH . $directory_name;
        $images = [];
        if (is_dir($directory_path)) {
            $files = scandir($directory_path);
            foreach ($files as $file) {
                $file_path = $directory_path . '/' . $file;
                if (is_file($file_path) && in_array(pathinfo($file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = [
                        'file_name' => $file,
                        'date_uploaded' => filemtime($file_path),
                        'file_size' => filesize($file_path),
                        'url' => BASE_URL . 'public/img/' . $directory_name . '/' . $file
                    ];
                }
            }
            usort($images, function ($a, $b) {
                if ($a['date_uploaded'] == $b['date_uploaded']) {
                    return strcasecmp($a['file_name'], $b['file_name']);
                }
                return ($a['date_uploaded'] > $b['date_uploaded']) ? -1 : 1;
            });
        }

        return $images;
    }

    /**
     * Displays a list of pages that can be managed.
     *
     * @return void
     */
    function manage(): void {
        $token = $this->_make_sure_allowed();

        // Check if the image folder exists and is writable
        $folder_path = APPPATH.'modules/trongate_pages/assets/images';
        if (!is_writable($folder_path)) {
            $data['view_module'] = 'trongate_pages';
            $this->view('permissions_error', $data);
            die();
        }

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['page_title'] = '%'.$searchphrase.'%';
            $params['page_body'] = '%'.$searchphrase.'%';
            $sql = 'select * from trongate_pages
            WHERE page_title LIKE :page_title
            OR page_body LIKE :page_body
            ORDER BY date_created desc';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Articles';
            $all_rows = $this->model->get('date_created desc');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'trongate_pages/manage';
        $pagination_data['record_name_plural'] = 'articles';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['form_location'] = str_replace('/manage', '/submit', current_url());
        $data['rows'] = $this->_reduce_rows($all_rows);
        //add author usernames
        $data['rows'] = $this->_add_author_usernames($data['rows']);
        $data['token'] = $token;
        $data['selected_per_page'] = $this->_get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'manage';
        $this->template($this->admin_template, $data);
    }

    /**
     * Displays a single page with the specified URL.
     *
     * @return void
     */
    function display(): void {
        $target_segment = (segment(2) !== 'display') ? 2 : 3;
        $record_obj = $this->model->get_one_where('url_string', segment($target_segment));
        $data = (array) $record_obj;
        $data['targetTable'] = 'trongate_pages';
        $data['recordId'] = $record_obj->id;
        $data['imgUploadApi'] = BASE_URL.'trongate_pages/submit_image_upload';

        $last_segment = SEGMENTS[count(SEGMENTS)-1];

        //is this user an 'admin' user?
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token(1);
        $data['enable_page_edit'] = false;

        if(($last_segment === 'edit') && ($token !== false)) {
            $data['enable_page_edit'] = true;
        } elseif (($last_segment === 'edit') && ($token === false)) {
            if(strtolower(ENV) === 'dev') {
                redirect('trongate_pages/manage');
            }
        }

        if (($data['published'] === 0) && ($last_segment !== 'edit')) {
            //page not published
            load('error_404');
            die();
        }

        $data['sample_text'] = $this->sample_text;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'display';
        $this->template($this->page_template, $data);
    }

    /**
     * Attempt to active page edit functionality for the given Trongate pages data.
     *
     * @param array $trongate_pages_data An associative array of Trongate pages data.
     * @return void
     */
    function _attempt_enable_page_edit(array $trongate_pages_data): void {
        $enable_page_edit = $trongate_pages_data['enable_page_edit'] ?? false;

        if($enable_page_edit === true) {
            $this->module('trongate_tokens');
            $trongate_pages_data['trongate_token'] = $this->trongate_tokens->_attempt_get_valid_token();
            $this->view('enable_page_edit', $trongate_pages_data);
        }
    }
    
    /**
     * Accepts an array of records and reduces array size,
     * based on current $limit and $offset values.
     *
     * @param array $all_rows
     * @return array
     */
    function _reduce_rows(array $all_rows): array {
        $rows = [];
        $start_index = $this->_get_offset();
        $limit = $this->_get_limit();
        $end_index = $start_index + $limit;
        $article_trigger = defined('TRONGATE_PAGES_TRIGGER') ? TRONGATE_PAGES_TRIGGER : 'trongate_pages/display';

        $count = -1;
        foreach ($all_rows as $article) {
            $count++;
            if (($count>=$start_index) && ($count<$end_index)) {
                $article->published = ($article->published == 1 ? 'yes' : 'no');
                $article->article_url = BASE_URL.$article_trigger.'/'.$article->url_string;
                $rows[] = $article;
            }
        }

        return $rows;
    }

    /**
     * Submit page title & create new database record.
     *
     * @return void
     */
    function submit(): void {
        $trongate_token = $this->_make_sure_allowed();

        $this->module('trongate_tokens');
        $trongate_user_id = $this->trongate_tokens->_get_user_id();

        $this->validation_helper->set_rules('page_title', 'page title', 'required|min_length[2]|callback_title_check');
        $result = $this->validation_helper->run();

        if ($result == true) {
            $data['page_title'] = post('page_title', true);
            $data['meta_keywords'] = '';
            $data['meta_description'] = '';
            $data['page_body'] = '';
            $data['date_created'] = time();
            $data['last_updated'] = time();
            $data['published'] = 0;
            $data['created_by'] = $trongate_user_id;
            $data['url_string'] = $this->_make_url_str($data['page_title']);
            $update_id = $this->model->insert($data, 'trongate_pages');
            redirect('trongate_pages/display/'.$data['url_string'].'/edit');
        } else {
            $this->manage();
        }
    }

    function submit_delete() {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = (int) segment(3);

        if (($submit == 'Yes - Delete Now') && ($params['update_id']>0)) {
            //delete all of the comments associated with this record
            $sql = 'delete from trongate_comments where target_table = :module and update_id = :update_id';
            $params['module'] = 'trongate_pages';
            $this->model->query_bind($sql, $params);

            //delete the record
            $this->model->delete($params['update_id'], 'trongate_pages');

            //set the flashdata
            $flash_msg = 'The record was successfully deleted';
            set_flashdata($flash_msg);

            //redirect to the manage page
            redirect('trongate_pages/manage');
        }
    }


    /**
     * Handles the submission of an image upload and saves the file to the server.
     *
     * @return void
     *
     * @throws Exception If no file was submitted
     */
    function submit_image_upload(): void {
        api_auth();

        $update_id = segment(3, 'int');

        //make sure the trongate_pages table exists
        $rows = $this->model->query('show tables', 'array');
        $all_tables = [];
        foreach($rows as $row_key => $row_value) {
            foreach($row_value as $key => $table_name) {
                $all_tables[] = $table_name;
            }
        }

        if (!in_array('trongate_pages', $all_tables)) {
            http_response_code(400);

            $error_msg = (strtolower(ENV) === 'dev') ? 'trongate_pages table not found!' : 'Invalid request!';
            echo $error_msg;
            die();
        }

        try {
            if (isset($_FILES["file1"])) {
                $fileName = $_FILES["file1"]["name"]; // The file name
                $fileTmpLoc = $_FILES["file1"]["tmp_name"]; // File in the PHP tmp folder
                $fileType = $_FILES["file1"]["type"]; // The type of file it is
                $fileSize = $_FILES["file1"]["size"]; // File size in bytes
                $fileErrorMsg = $_FILES["file1"]["error"]; // 0 for false... and 1 for true
            } else {
                http_response_code(400);
                echo 'No file was submitted';
                die();
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo "Error: " . $e->getMessage();
            die();
        }

        $destination_dir = '../modules/trongate_pages/assets/images/uploads';

        if (!$fileTmpLoc) { // if file not chosen
            http_response_code(400);
            echo "Please browse for a file before clicking the upload button.";
            die();
        }

        $max_file_size_mb = $this->max_file_size_mb; // set maximum allowed file size in megabytes
        $max_width = $this->max_width; // set maximum allowed width
        $max_height = $this->max_height; // set maximum allowed height

        $file_validation_result = $this->validate_file($fileTmpLoc, $max_file_size_mb, $max_width, $max_height);

        if ($file_validation_result['error']) {
            http_response_code(400);
            echo $file_validation_result['message'];
            die();
        }

        $fileName = $this->prep_file_name($fileName, $destination_dir); // guarantee that file names are not problematic

        if(move_uploaded_file($fileTmpLoc, $destination_dir.'/'.$fileName)){
            http_response_code(200);
            $abs_path1 = BASE_URL.'trongate_pages_module/';
            $replace = $destination_dir.'/'.$fileName;
            $picture_path = str_replace('/modules/trongate_pages/assets/', $abs_path1, $replace);
            echo '|||'.$picture_path;
            die();
        } else {
            http_response_code(400);
            echo 'Unable to upload to: '.$destination_dir.'/'.$fileName; die();
            echo "move_uploaded_file function failed";
            die();
        }
    }

    /**
     * Deletes an image from the server.
     * 
     * @return void
     */
    function submit_delete_image(): void {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (isset($data->fileName)) {
            $file_name = $data->fileName;
            $directory_name = 'modules/trongate_pages/assets/images/uploads';
            $directory_path = APPPATH . $directory_name;
            $file_path = $directory_path . '/' . $file_name;

            // Check if the file exists and it's a picture
            if (file_exists($file_path) && strpos(mime_content_type($file_path), 'image/') === 0) {
                if (is_writable($file_path)) {
                    if (unlink($file_path)) {
                        http_response_code(200);
                        echo 'Image deleted successfully.';
                    } else {
                        http_response_code(500);
                        echo 'Failed to delete the image. Please try again later.';
                    }
                } else {
                    http_response_code(403);
                    echo 'Permission denied. You do not have the necessary permissions to delete the image.';
                }
            } else {
                http_response_code(415); // Unsupported Media Type
                echo 'The specified file is either not found or not an image.';
            }
        } else {
            http_response_code(400);
            echo 'Invalid request. Please provide a valid file name.';
        }
    }

    /**
     * Submit and beautify HTML content.
     *
     * @return void
     */
    function submit_beautify(): void {
        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);
        if (isset($data->raw_html)) {
            $raw_html = $data->raw_html;
            $nice_html = $this->beautify_html($raw_html, '    ');
            http_response_code(200);
            echo $nice_html;
        } else {
            http_response_code(400);
        }
    }

    /**
     * Beautify HTML content.
     *
     * @param string $content The HTML content to beautify.
     * @param string $tab The tab character for indentation.
     * @return string The beautified HTML content.
     */
    function beautify_html(string $content, string $tab = "\t"): string {
        $content = str_replace('  ', ' ', $content);
        $content = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $content);
        $token = strtok($content, "\n");
        $result = '';
        $pad = 0;
        $indent = 0;
        $matches = array();
        $voidTag = false; // Initialize voidTag variable
        while ($token !== false && strlen($token) > 0) {
            $padPrev = $padPrev ?? $pad;
            $token = trim($token);
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) {
                $indent = 0;
            } elseif (preg_match('/^<\/\w/', $token, $matches)) {
                $pad--;
                if ($indent > 0) $indent = 0;
            } elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) {
                foreach ($matches as $m) {
                    if (preg_match('/^<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)/im', $m)) {
                        $voidTag = true;
                        break;
                    }
                }
                $indent = 1;
            } else {
                $indent = 0;
            }
            if ($token == "<textarea>") {
                $line = str_pad($token, strlen($token) + $pad, $tab, STR_PAD_LEFT);
                $result .= $line;
                $token = strtok("\n");
                $pad += $indent;
            } elseif ($token == "</textarea>") {
                $line = $token;
                $result .= $line . "\n";
                $token = strtok("\n");
                $pad += $indent;
            } else {
                $line = str_pad($token, strlen($token) + $pad, $tab, STR_PAD_LEFT);
                $result .= $line . "\n";
                $token = strtok("\n");
                $pad += $indent;
                if ($voidTag) {
                    $voidTag = false;
                    $pad--;
                }
            }
        }
        return $result;
    }

    /**
     * An API endpoint for checking YouTube video ID or URL - outgputs the video ID if valid.
     *
     * @return void
     */
    function check_youtube_video_id(): void {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (!is_object($data)) {
            http_response_code(400); // Bad request
            die();
        }

        $video_id = isset($data->video_id) ? trim($data->video_id) : '';

        // Regular expression pattern to match YouTube video ID and exclude additional variables
        $pattern = '/^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:embed\/|watch\?v=|v\/|watch\?.+&v=|watch\/.+\/\?)|youtu\.be\/)([^&\?\/\s]+)/';

        // Check if the provided string matches the pattern
        if (preg_match($pattern, $video_id, $matches)) {

            $video_id_len = strlen($matches[1]);

            if ($video_id_len !== 11) {
                http_response_code(406); // Not acceptable
                die();            
            } else {
                http_response_code(200);
                echo $matches[1];
                die();
            }

        }

        // Check if the video ID is valid
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id)) {

            $video_id_len = strlen($video_id);

            if ($video_id_len !== 11) {
                http_response_code(406); // Not acceptable
                die();            
            } else {
                http_response_code(200);
                echo $video_id;
                die();
            }

        }

        http_response_code(406); // Not acceptable
    }



    function _get_limit() {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    function _get_offset() {
        $page_num = (int) segment(3);

        if ($page_num>1) {
            $offset = ($page_num-1)*$this->_get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    function _get_selected_per_page() {
        $selected_per_page = (isset($_SESSION['selected_per_page'])) ? $_SESSION['selected_per_page'] : 1;
        return $selected_per_page;
    }

    function set_per_page($selected_index) {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (!is_numeric($selected_index)) {
            $selected_index = $this->per_page_options[1];
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('trongate_pages/manage');
    }

    /**
     * Creates a URL string (slug) from the page title.
     *
     * @param string $page_title The page title.
     * @return string Returns a URL string (slug) based on page title,
     *                      or random string if URL string is not unique.
     */
    function _make_url_str(string $page_title): string {
        $url_string = url_title($page_title);
        $record_obj = $this->model->get_one_where('url_string', $url_string, 'trongate_pages');

        if ($record_obj !== false) {
            $url_string.= make_rand_str(8);
        }

        return $url_string;
    }

    /**
     * Add author usernames to the rows based on the 'created_by' values.
     *
     * @param array $rows The array of rows to modify.
     *
     * @return array The modified array of rows with added author usernames.
     */
    function _add_author_usernames(array $rows): array {
        $created_by_values = [];
        foreach ($rows as $row) {
            $created_by_values[] = $row->created_by;
        }

        if(count($created_by_values) === 0) {
            return $rows;
        }

        // Use the retrieved 'created_by' values to execute a separate query to retrieve the author usernames
        $sql = "SELECT trongate_user_id, username FROM trongate_administrators WHERE trongate_user_id IN (" . implode(",", $created_by_values) . ")";
        $user_rows = $this->model->query($sql, 'object');
        $all_authors = [];
        foreach($user_rows as $user_row) {
            $all_authors[$user_row->trongate_user_id] = $user_row->username;
        }

        foreach($rows as $row_key => $row) {
            $created_by = $row->created_by;
            if(isset($all_authors[$created_by])) {
                $rows[$row_key]->author =  $all_authors[$created_by];
            } else {
                $rows[$row_key]->author = 'Unknown';
            }
        }

        return $rows;
    }

    function _get_data_from_db($update_id) {
        $record_obj = $this->model->get_where($update_id, 'trongate_pages');

        if ($record_obj == false) {
            $this->template('error_404');
            die();
        } else {
            $data = (array) $record_obj;
            return $data;        
        }
    }

    function _get_data_from_post() {
        $data['page_title'] = post('page_title', true);
        $data['meta_keywords'] = post('meta_keywords', true);
        $data['meta_description'] = post('meta_description', true);
        $data['page_body'] = post('page_body', true);
        $data['date_created'] = post('date_created', true);
        $data['last_updated'] = post('last_updated', true);
        $data['published'] = post('published', true);
        $data['created_by'] = post('created_by', true);        
        return $data;
    }

    /**
     * Validation callback:  Make sure page title is unique.
     *
     * @param string $str The page title to be checked.
     * @return string|bool Returns an error message string if the page title is not unique,
     *                      or true if the page title is unique.
     */
    function title_check(string $str): string|bool {
        $page_title = trim(strip_tags($str));
        $page_title = preg_replace("/[ ]+/", " ", $page_title);
        $charset = (defined('CHARSET')) ? CHARSET : 'UTF-8';
        $page_title = htmlspecialchars($page_title, ENT_QUOTES, $charset);

        //make sure page title is unique
        $record_obj = $this->model->get_one_where('page_title', $page_title, 'trongate_pages');
        if ($record_obj !== false) {
            $error_msg = 'The page title that you submitted is not available.';
            return $error_msg;
        } else {
            return true;
        }
    }


    /**
     * Prepare the file name for uploading.
     *
     * @param string $original_file_name The original file name.
     * @param string $destination_dir The destination directory for the file.
     * @return string The prepared file name.
     */
    function prep_file_name(string $original_file_name, string $destination_dir): string {
        $directory_name = str_replace('../', '', $destination_dir);
        $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
        $original_file_name_without_extension = pathinfo($original_file_name, PATHINFO_FILENAME);
        $slug = url_title($original_file_name_without_extension);

        // Replace hyphens with underscores
        $slug = str_replace('-', '_', $slug);

        // Limit file name size to 33 characters
        $max_filename_length = 33;
        $file_name = substr($slug, 0, $max_filename_length);

        // Add an integer onto the filename if a file already exists with that file name
        $i = 1;
        $file_path = $directory_name . '/' . $file_name . '.' . $file_extension;

        while (file_exists(APPPATH . $file_path)) {
            $i++;
            $file_path = $directory_name . '/' . $file_name . $i . '.' . $file_extension;
        }

        $safe_file_name = $i > 1 ? $file_name . $i . '.' . $file_extension : $file_name . '.' . $file_extension;
        return strtolower($safe_file_name);
    }

    /**
     * Validate the uploaded file based on size and dimensions.
     *
     * @param string $fileTmpLoc The temporary location of the uploaded file.
     * @param int $max_file_size_mb The maximum allowed file size in megabytes.
     * @param int $max_width The maximum allowed width for the image.
     * @param int $max_height The maximum allowed height for the image.
     * @return array An array containing the validation result with 'error' and 'message' keys.
     */
    function validate_file(string $fileTmpLoc, int $max_file_size_mb, int $max_width, int $max_height): array {
        $file_size_mb = round(filesize($fileTmpLoc) / 1048576, 2); // Calculate file size in megabytes
        $image_size = getimagesize($fileTmpLoc); // Get image size

        $validation_result = [
            'error' => false,
            'message' => ''
        ];

        // Check file size
        if ($file_size_mb > $max_file_size_mb) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded file exceeds the maximum allowed file size of ' . $max_file_size_mb . 'MB.';
            return $validation_result;
        }

        // Check image size
        $width = $image_size[0];
        $height = $image_size[1];

        if ($width > $max_width) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded image width exceeds the maximum allowed width of ' . $max_width . 'px.';
            return $validation_result;
        }

        if ($height > $max_height) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded image height exceeds the maximum allowed height of ' . $max_height . 'px.';
            return $validation_result;
        }

        return $validation_result;
    }


    
  } //end of the class 