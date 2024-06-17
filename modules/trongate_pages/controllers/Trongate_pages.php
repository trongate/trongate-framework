<?php
class Trongate_pages extends Trongate {

    private $page_template = 'public';
    private $admin_template = 'admin';
    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100);
    private $max_file_size_mb = 5; // maximum allowed file size (for images) in megabytes
    private $max_width = 4200; // maximum allowed width for image uploads
    private $max_height = 3200; // set maximum allowed height for image uploads

    private $sample_text = 'Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis. Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit.';

    /**
     * Attempt to display a page based on the URL segment.
     *
     * @return void
     */
    public function display(): void {

        $enable_page_edit = false;
        $target_segment = get_last_segment();

        if (current_url() === BASE_URL) {

            if (strtolower(ENV) === 'dev') {
                $num_rows = $this->model->count('trongate_pages');
                if ($num_rows === 0) {
                    $this->create_homepage_record();
                }
            }

            $record_obj = $this->model->get_where(1, 'trongate_pages');

        } else {
            $last_segment = $target_segment;
        }

        if ($target_segment === 'edit') {
            $this_current_url = rtrim(current_url(), '/');
            $url_segments = explode('/', $this_current_url);
            $target_segment_index = count($url_segments) - 2;
            $target_segment = $url_segments[$target_segment_index];

            // Is this user an 'admin' user?
            $this->module('trongate_tokens');
            $token = $this->trongate_tokens->_attempt_get_valid_token(1);

            if (($token === false) && (strtolower(ENV) === 'dev')) {
                redirect('trongate_pages/manage');
            } else {
                // User is now confirmed as being 'admin'.
                $enable_page_edit = true;                
            }
        }

        if (!isset($record_obj)) {
            $record_obj = $this->model->get_one_where('url_string', $target_segment, 'trongate_pages');
        }
        
        if ($record_obj === false) {
            // No matching record found on trongate_pages table.
            $this->template('error_404', []);
            return;
        }

        $data = (array) $record_obj;
        $data['tgp_invite_clear_home'] = 0;

        if ($enable_page_edit === false) {
            $data['page_body'] = str_replace('[website]', BASE_URL, $data['page_body']);
        } else {
            $record_id = $record_obj->id ?? 0;
            $last_updated = $record_obj->last_updated ?? 1;
            if (($record_id === 1) && ($last_updated === 0)) { 
                $data['tgp_invite_clear_home'] = 1; // Invite first time admin visitors to empty the homepage contents.
            }
        }

        $data['enable_page_edit'] = $enable_page_edit;
        $data['targetTable'] = 'trongate_pages';
        $data['recordId'] = $record_obj->id;
        $data['imgUploadApi'] = BASE_URL . 'trongate_pages/submit_image_upload';

        // Produce a 404 page IF this page is not published.
        if (($data['published'] === 0) && ($last_segment !== 'edit')) {
            load('error_404');

            if (strtolower(ENV) === 'dev') {
                // Add an alert when in 'dev' mode - to let developers know why page is not displaying.
                $data['view_module'] = 'trongate_pages';
                $this->view('not_published_info', $data);
            }

            die();
        }

        $data['sample_text'] = $this->sample_text;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'display';
        $this->template($this->page_template, $data);
    }

    /**
     * Restores the 'default' homepage content.
     *
     * @return void
     */
    public function reset(): void {
        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            die();
        }

        $data['page_body'] = $this->view('default_homepage_content', [], true);
        $data['last_updated'] = 0;
        $this->model->update(1, $data);
        http_response_code(200);
        echo 'Homepage has been successfully reset.';
    }

    /**
     * Ensures that the current user is allowed to access the protected resource.
     * Feel free to change to suit your own individual use case.
     *
     * @return string|false The security token if the user is authorized, or false otherwise.
     */
    public function _make_sure_allowed(): string|false {
        //by default, 'admin' users (i.e., users with user_level_id === 1) are allowed
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        return $token;
    }

    /**
     * Displays a list of pages that can be managed.
     *
     * @return void
     */
    public function manage(): void {
        $token = $this->_make_sure_allowed();

        // Check if the image folder exists and is writable
        $folder_path = APPPATH . 'modules/trongate_pages/assets/images/uploads';
        if (!is_writable($folder_path)) {
            $data['view_module'] = 'trongate_pages';
            $this->view('permissions_error', $data);
            die();
        }

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['page_title'] = '%' . $searchphrase . '%';
            $params['page_body'] = '%' . $searchphrase . '%';
            $sql = 'select * from trongate_pages
            WHERE page_title LIKE :page_title
            OR page_body LIKE :page_body
            ORDER BY date_created desc';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Webpages';
            $all_rows = $this->model->get('id');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->get_limit();
        $pagination_data['pagination_root'] = 'trongate_pages/manage';
        $pagination_data['record_name_plural'] = 'webpages';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['form_location'] = str_replace('/manage', '/submit', current_url());
        $data['rows'] = $this->reduce_rows($all_rows);
        $data['rows'] = $this->add_publilc_urls($data['rows']);
        //add author usernames
        $data['rows'] = $this->add_author_usernames($data['rows']);
        $data['token'] = $token;
        $data['selected_per_page'] = $this->get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'manage';
        $this->template($this->admin_template, $data);
    }

    /**
     * Submit page title & create new database record.
     *
     * @return void
     */
    public function submit(): void {
        $trongate_token = $this->_make_sure_allowed();

        $this->module('trongate_tokens');
        $trongate_user_id = $this->trongate_tokens->_get_user_id();

        $this->validation_helper->set_rules('page_title', 'page title', 'required|min_length[2]|callback_title_check');
        $result = $this->validation_helper->run();

        if ($result == true) {
            $data['page_title'] = post('page_title', true);
            $data['meta_keywords'] = '';
            $data['meta_description'] = '';
            $data['page_body'] = '<h1>' . $data['page_title'] . '</h1>';
            $data['date_created'] = time();
            $data['last_updated'] = time();
            $data['published'] = 0;
            $data['created_by'] = $trongate_user_id;
            $data['url_string'] = $this->make_url_str($data['page_title']);
            $update_id = $this->model->insert($data, 'trongate_pages');
            redirect('trongate_pages/display/' . $data['url_string'] . '/edit');
        } else {
            $this->manage();
        }
    }

    /**
     * Handles the submission of an image upload and saves the file to the server.
     *
     * @return void
     *
     * @throws Exception If no file was submitted
     */
    public function submit_image_upload(): void {
        api_auth();
        $current_img_dir = $_POST['currentImgDir'] ?? '';
        $update_id = segment(3, 'int');

        //make sure the trongate_pages table exists
        $rows = $this->model->query('show tables', 'array');
        $all_tables = [];
        foreach ($rows as $row_key => $row_value) {
            foreach ($row_value as $key => $table_name) {
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

        if ($current_img_dir !== '') {
            $destination_dir .= $current_img_dir;
        }

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

        if (move_uploaded_file($fileTmpLoc, $destination_dir . '/' . $fileName)) {
            http_response_code(200);
            $destination_dir = str_replace('../', '/', $destination_dir);
            $abs_path1 = BASE_URL . 'trongate_pages_module/';
            $replace = $destination_dir . '/' . $fileName;
            $picture_path = str_replace('/modules/trongate_pages/assets/', $abs_path1, $replace);
            echo $picture_path;
            die();
        } else {
            http_response_code(400);
            echo 'Unable to upload to: ' . $destination_dir . '/' . $fileName;
            die();
            echo "move_uploaded_file function failed";
            die();
        }
    }

    /**
     * Create new folder.
     *
     * @return void
     */
    public function submit_create_new_img_folder(): void {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            die();
        }

        $new_folder_name = $data->newFolderName ?? '';
        $current_img_dir = $data->currentImgDir ?? '';

        if (strlen($current_img_dir) > 0) {
            if ($current_img_dir[0] === '/') {
                $current_img_dir = substr($current_img_dir, 1);
            }
        }

        $folder_name = html_entity_decode($new_folder_name, ENT_QUOTES, 'UTF-8');
        $folder_name = preg_replace('~[^\pL\d]+~u', '_', $folder_name);
        $folder_name = trim($folder_name, '_ ');
        $folder_name = strtolower($folder_name);

        // Check if the current img dir exists
        $root_dir_path = APPPATH . 'modules/trongate_pages/assets/images/uploads/' . trim($current_img_dir);

        if (!is_dir($root_dir_path)) {
            http_response_code(400);
            echo 'Invalid root dir!';
            die();
        }

        $root_dir_path = rtrim($root_dir_path, "/");

        // Make sure new folder does not already exist.
        $new_folder_path = $root_dir_path . '/' . $folder_name;
        $new_folder_path = rtrim($new_folder_path, "/");

        if (is_dir($new_folder_path)) {
            http_response_code(400);
            echo 'Folder exists!';
            die();
        }

        try {
            // Create the folder
            if (!file_exists($new_folder_path)) {
                mkdir($new_folder_path, 0755, true);
            }

            // Create an empty 'index.php' file inside the folder
            $index_file_path = $new_folder_path . '/index.php';
            if (!file_exists($index_file_path)) {
                file_put_contents($index_file_path, '');
            }

            // Set the folder permissions to 755
            chmod($new_folder_path, 0755);
            http_response_code(200);
            echo $new_folder_path;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'An error occurred: ' . $e->getMessage();
            die();
        }
    }

    /**
     * Rename an existing folder.
     *
     * @return void
     */
    public function submit_rename_img_folder(): void {
        api_auth();
        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            die();
        }

        $old_folder_name = $data->oldFolderName ?? '';
        $new_folder_name = $data->newFolderName ?? '';
        $current_img_dir = $data->currentImgDir ?? '';

        // Ensure the new folder name contains only safe characters
        $new_folder_name = preg_replace('/[^a-zA-Z0-9-_]/', '', $new_folder_name);

        if (empty($new_folder_name)) {
            http_response_code(400);
            echo 'New folder name contains invalid characters or is empty.';
            die();
        }

        $old_folder_path = APPPATH . 'modules/trongate_pages/assets/images/uploads' . trim($current_img_dir) . '/' . $old_folder_name;
        $new_folder_path = APPPATH . 'modules/trongate_pages/assets/images/uploads' . trim($current_img_dir) . '/' . $new_folder_name;

        // Check if the old folder exists
        if (!is_dir($old_folder_path)) {
            http_response_code(400);
            echo 'Old folder does not exist!';
            die();
        }

        // Make sure new folder does not already exist.
        if (is_dir($new_folder_path)) {
            http_response_code(400);
            echo 'New folder already exists!';
            die();
        }

        try {
            // Rename the folder
            if (rename($old_folder_path, $new_folder_path)) {
                http_response_code(200);
                $new_folder_name = basename($new_folder_path);
                echo $new_folder_name;
            } else {
                http_response_code(500);
                echo 'Failed to rename the folder.';
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo 'An error occurred: ' . $e->getMessage();
            die();
        }
    }

    /**
     * Deletes a folder from the server.
     * 
     * @return void
     */
    public function submit_delete_folder(): void {

        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            die();
        }

        $submitted_folder_name = $data->folderName ?? '';
        $current_img_dir = $data->currentImgDir ?? '';

        // Check if the current img dir exists
        $root_dir_path = APPPATH . 'modules/trongate_pages/assets/images/uploads/';

        if ($current_img_dir !== '') {
            $current_img_dir = ltrim($current_img_dir, '/');
            $root_dir_path .= $current_img_dir;
        }

        if (!is_dir($root_dir_path)) {
            http_response_code(400);
            echo 'Invalid root dir!';
            die();
        }

        // Make sure folder exists.
        $target_folder_path = $root_dir_path . '/' . $submitted_folder_name;

        if (!is_dir($target_folder_path)) {
            http_response_code(400);
            echo 'Folder does not exist!';
            die();
        }

        $this->delete_directory($target_folder_path);
        http_response_code(200);
    }

    /**
     * Submit and beautify HTML content.
     *
     * @return void
     */
    public function submit_beautify(): void {
        api_auth();
        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);
        if (isset($data->page_body)) {
            $page_body = $data->page_body;
            $nice_html = $this->beautify_html($page_body, '    ');

            http_response_code(200);
            echo $nice_html;
        } else {
            http_response_code(400);
        }
    }

    /**
     * Submit a request to delete a record.
     *
     * @return void
     */
    public function submit_delete(): void {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = (int) segment(3);

        if (($submit == 'Yes - Delete Now') && ($params['update_id'] > 1)) {
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
        } elseif ($params['update_id'] === 1) {
            $form_submission_errors['update_id'][] = 'Deletion of the homepage record is not permitted.';
            $_SESSION['form_submission_errors'] = $form_submission_errors;
            redirect('trongate_pages/manage');
        }
    }

    /**
     * Deletes an image from the server.
     * 
     * @return void
     */
    public function submit_delete_image(): void {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (isset($data->fileName)) {
            $file_name = $data->fileName;
            $directory_name = 'modules/trongate_pages/assets/images/uploads';
            $current_img_dir = $data->currentImgDir ?? '';

            if ($current_img_dir !== '') {
                $directory_name .= $current_img_dir;
            }

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
     * Attempts to display a page based on the URL segment by invoking the display() method of the Trongate Pages module.
     *
     * This method delegates the task of displaying a page to the display() method of the Trongate Pages module.
     * It handles scenarios such as checking if the user is an admin for editing permissions and ensuring that only published pages are displayed.
     *
     * @return void
     */
    public function attempt_display(): void {
        $this->display();
    }

    /**
     * Creates a URL string (slug) from the page title.
     *
     * @param string $page_title The page title.
     * @return string Returns a URL string (slug) based on page title,
     *                      or random string if URL string is not unique.
     */
    public function make_url_str(string $page_title): string {
        $url_string = url_title($page_title);
        $record_obj = $this->model->get_one_where('url_string', $url_string, 'trongate_pages');

        if ($record_obj !== false) {
            $all_website_pages = $this->model->get('id', 'trongate_pages');
            $url_string = $this->make_url_string_unique($url_string, $all_website_pages, $record_obj->id);
        }

        return $url_string;
    }

    /**
     * Attempt to active page edit functionality for the given Trongate pages data.
     *
     * @param array $trongate_pages_data An associative array of Trongate pages data.
     * @return void
     */
    public function _attempt_enable_page_edit(array $trongate_pages_data): void {
        $enable_page_edit = $trongate_pages_data['enable_page_edit'] ?? false;

        if ($enable_page_edit === true) {
            $this->module('trongate_tokens');
            $trongate_pages_data['trongate_token'] = $this->trongate_tokens->_attempt_get_valid_token();

            $directory = APPPATH . 'public/trongate_pages_extra';

            if (is_dir($directory)) {
                // Set up two additional arrays to be included (one js and one css)
                $js_directory = $directory . '/js';
                $additional_files_js = $this->fetch_additional_files($js_directory);

                if (count($additional_files_js) > 0) {
                    $trongate_pages_data['additional_files_js'] = $additional_files_js;
                }

                $css_directory = $directory . '/css';
                $additional_files_css = $this->fetch_additional_files($css_directory);

                if (count($additional_files_css) > 0) {
                    $trongate_pages_data['additional_files_css'] = $additional_files_css;
                }
            }

            $this->view('enable_page_edit', $trongate_pages_data);
        }
    }

    /**
     * Executes a before hook that updates the input data and sets the 'last_updated' parameter to the current time.
     *
     * @param array $input The input data to be updated.
     * @return array The updated input data.
     */
    public function _pre_update(array $input): array {

        if (isset($input['params']['page_title'])) {
            $page_title = trim($input['params']['page_title']);

            if ($page_title == '') {
                http_response_code(400);
                echo 'Invalid page title!';
                die();
            }

        }

        $input['params']['last_updated'] = time();
        return $input;
    }

    /**
     * Fetches uploaded images from the 'assets/images/uploads' directory, within the 'Trongate Pages' module.
     * This function retrieves both directories and images within the specified directory.
     *
     * @return array Returns an array containing information about directories and images.
     *               Each element in the array represents either a directory or an image.
     *               Directories are represented by associative arrays containing 'info' and 'type' keys,
     *               where 'info' holds the directory name and 'type' is set to 'directory'.
     *               Images are represented by associative arrays containing 'file_name', 'date_uploaded',
     *               'file_size', 'url', and 'type' keys. 'type' is set to 'image'.
     */
    public function fetch_uploaded_images(): array {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo 'Invalid inbound JSON.';
            die();
        } else {
            $current_img_dir = $data->currentImgDir ?? '';
        }

        $directory_name = 'modules/trongate_pages/assets/images/uploads';

        if ($current_img_dir !== '') {
            $directory_name .= trim($current_img_dir);
        }

        $directories = $this->fetch_directories($directory_name, true);
        $images = $this->get_images_in_directory($directory_name, true);

        if (count($directories) > 0) {
            $images = array_merge($directories, $images);
        }

        http_response_code(200);
        echo json_encode($images);

        return $images;
    }

    /**
     * Validation callback:  Make sure page title is unique.
     *
     * @param string $str The page title to be checked.
     * @return string|bool Returns an error message string if the page title is not unique,
     *                      or true if the page title is unique.
     */
    public function title_check(string $str): string|bool {
        $page_title = trim(strip_tags($str));
        $page_title = preg_replace("/[ ]+/", " ", $page_title);
        $charset = (defined('CHARSET')) ? CHARSET : 'UTF-8';
        $page_title = htmlspecialchars($page_title, ENT_QUOTES, $charset);

        // Make sure URL string will not conflict with existing module.
        $url_string = url_title($page_title);

        if (strlen($url_string) > 0) {

            $module_exists = $this->module_exists($url_string);

            if ($module_exists === true) {
                $error_msg = 'The page title that you submitted conflicts with a pre-existing module named, <b>'.$url_string.'</b>.';
                return $error_msg;            
            }

        }

        // Make sure page title is unique.
        $record_obj = $this->model->get_one_where('page_title', $page_title, 'trongate_pages');
        if ($record_obj !== false) {
            $error_msg = 'The page title that you submitted is not available.';
            return $error_msg;
        } else {
            return true;
        }
    }

    /**
     * An API endpoint for checking YouTube video ID or URL - outgputs the video ID if valid.
     *
     * @return void
     */
    public function check_youtube_video_id(): void {
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

    /**
     * Creates a homepage record.
     *
     * @return void Returns nothing.
     */
    private function create_homepage_record(): void {
        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            die();
        }

        $row_data['id'] = 1;
        $row_data['url_string'] = 'homepage';
        $row_data['page_title'] = 'Homepage';
        $row_data['meta_keywords'] = '';
        $row_data['meta_description'] = '';

        $data['view_module'] = 'trongate_pages';
        $row_data['page_body'] = $this->view('default_homepage_content', $data, true);
        $row_data['date_created'] = time();
        $row_data['last_updated'] = 0;
        $row_data['published'] = 1;
        $row_data['created_by'] = 1;
        $this->model->insert($row_data, 'trongate_pages');
    }

    /**
     * Recursively deletes a directory and all its contents.
     *
     * @param string $target_folder_path The path of the target folder to delete.
     * @return void
     * @throws Exception If an error occurs while deleting the directory.
     */
    private function delete_directory(string $target_folder_path): void {
        try {
            if (!is_dir($target_folder_path)) {
                return;
            }
            $files = scandir($target_folder_path);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $file_path = $target_folder_path . '/' . $file;
                if (is_dir($file_path)) {
                    $this->delete_directory($file_path);
                } else {
                    unlink($file_path);
                }
            }
            rmdir($target_folder_path);
        } catch (Exception $e) {
            // Handle the exception here
            http_response_code(400);
            echo 'Error deleting directory: ' . $e->getMessage();
        }
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
    private function validate_file(string $fileTmpLoc, int $max_file_size_mb, int $max_width, int $max_height): array {
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

    /**
     * Beautify HTML content.
     *
     * @param string $content The HTML content to beautify.
     * @param string $tab The tab character for indentation.
     * @return string The beautified HTML content.
     */
    private function beautify_html(string $content, string $tab = "\t"): string {
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
     * Generates a unique URL string by appending search-friendly strings or a random string if all variations are exhausted.
     *
     * This function checks if the provided temporary URL string is unique by comparing it with existing URL strings in the given array of website pages.
     * If the URL string is not unique, it appends search-friendly strings from a predefined list until a unique URL string is obtained.
     * If all variations of search-friendly strings have been exhausted without finding a unique URL string, it falls back to generating a random string.
     * A maximum number of iterations is set to prevent infinite looping.
     *
     * @param string $temp_url_string The temporary URL string to be made unique.
     * @param array $all_website_pages An array of all records (as objects) from the 'trongate_pages' table.
     * @param int $record_id The ID of the website page record for which a unique URL string is being generated (default: 0).
     * @return string The unique URL string.
     */
    private function make_url_string_unique(string $temp_url_string, array $all_website_pages, int $record_id = 0): string {
        // Check if there are any matches for the provided URL string
        $got_matches = $this->got_matches($temp_url_string, $all_website_pages, $record_id);

        // If the URL string is already unique, return it
        if ($got_matches === false) {
            return $temp_url_string;
        }

        // List of search-friendly strings to append to the URL string
        $search_friendly_strings = array(
            "information", "advice", "details", "insights", "data",
            "tips", "facts", "knowledge", "solutions", "resources",
            "overview", "explanation", "learn-more", "deep-dive",
            "how-to", "best-practices", "explore", "in-depth",
            "essentials", "analysis", "research", "walk-through"
        );

        // Append search-friendly strings until a unique URL string is obtained or maximum iterations reached
        $unique_url_string = $temp_url_string;
        $max_iterations = 10; // Maximum number of iterations to prevent infinite looping
        $iterations = 0;

        while ($got_matches === true && $iterations < $max_iterations) {
            $random_key = array_rand($search_friendly_strings);
            $random_string = $search_friendly_strings[$random_key];
            $unique_url_string .= '-' . $random_string;
            $got_matches = $this->got_matches($unique_url_string, $all_website_pages, $record_id);
            $iterations++;
        }

        // If maximum iterations reached without finding a unique URL string, fallback to generating a random string
        if ($iterations === $max_iterations) {
            $unique_url_string .= '-' . make_rand_str(8); // Adjust length as needed
        }

        return $unique_url_string;
    }

    /**
     * Check if there are any matches for a given temporary URL string.
     *
     * @param string $temp_url_string The temporary URL string to check for matches.
     * @param array $all_website_pages An array of all website pages.
     * @param int $record_id The record ID to exclude from matching.
     *
     * @return bool True if matches are found, false otherwise.
     */
    private function got_matches(string $temp_url_string, array $all_website_pages, int $record_id): bool {
        //fetch all url_strings that match our temp_url_string
        $matches = [];
        foreach ($all_website_pages as $website_page) {
            $id = (int) $website_page->id;
            $url_string = trim($website_page->url_string);
            if (($url_string === $temp_url_string) && ($id !== $record_id)) {
                $matches[$id] = $url_string;
            }
        }

        $num_matches = count($matches);
        if ($num_matches > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fetches a list of files in the specified directory with full website paths.
     *
     * @param string $directory The directory path to fetch files from.
     *
     * @return array An array containing the full website paths of the files in the directory.
     *               The array may be empty if the directory does not exist or has no files.
     */
    private function fetch_additional_files(string $directory): array {
        $file_list = array(); // Initialize an empty array to store website paths

        // Check if the directory exists
        if (is_dir($directory)) {
            // Use scandir to get the list of files and directories in the specified directory
            $files = scandir($directory);
            $alt_directory = BASE_URL . str_replace(array(APPPATH, 'public/'), '', $directory);

            // Loop through the files and add their full website paths to the file list
            foreach ($files as $file) {
                // Exclude "." and ".." entries, which are references to the current and parent directories
                if ($file != "." && $file != "..") {
                    $file_list[] = $alt_directory . '/' . $file; // Add the full website path to the array
                }
            }
        }

        return $file_list; // Return the array of website paths (could be empty)
    }

    /**
     * Prepare the file name for uploading.
     *
     * @param string $original_file_name The original file name.
     * @param string $destination_dir The destination directory for the file.
     * @return string The prepared file name.
     */
    private function prep_file_name(string $original_file_name, string $destination_dir): string {
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
     * Get the limit for the number of records per page.
     *
     * @return int The limit for the number of records per page.
     */
    private function get_limit(): int {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    /**
     * Get the offset for pagination based on the current page number.
     *
     * @return int The offset for pagination.
     */
    private function get_offset(): int {
        $page_num = (int) segment(3);

        if ($page_num > 1) {
            $offset = ($page_num - 1) * $this->get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    /**
     * Accepts an array of records and reduces array size,
     * based on current $limit and $offset values.
     *
     * @param array $all_rows
     * @return array
     */
    private function reduce_rows(array $all_rows): array {
        $rows = [];
        $start_index = $this->get_offset();
        $limit = $this->get_limit();
        $end_index = $start_index + $limit;
        $webpage_trigger = defined('TRONGATE_PAGES_TRIGGER') ? TRONGATE_PAGES_TRIGGER : 'trongate_pages/display';

        $count = -1;
        foreach ($all_rows as $webpage) {
            $count++;
            if (($count >= $start_index) && ($count < $end_index)) {
                $webpage->published = ($webpage->published == 1 ? 'yes' : 'no');
                $webpage->webpage_url = BASE_URL . $webpage_trigger . '/' . $webpage->url_string;
                $rows[] = $webpage;
            }
        }

        return $rows;
    }

    /**
     * Enhances an array of website pages by adding a public URL property to each page record.
     *
     * This method is part of the Trongate Pages class. It iterates through an array of website page objects and assigns
     * a 'webpage_url_public' property to each. The value of this property is determined based on whether a module exists
     * with the same name as the URL string of the page. If such a module does not exist, a simplified URL is assigned;
     * otherwise, the existing 'website_url' is used.
     *
     * @param array $rows An array of website page objects.
     * @return array The modified array of website page objects, each with a 'webpage_url_public' property.
     */
    private function add_publilc_urls(array $rows): array {
        foreach ($rows as $key => $value) {
            $url_string = $value->url_string;
            $module_exists = $this->module_exists($url_string);
            if ($module_exists === false) {
                $rows[$key]->webpage_url_public = BASE_URL . $url_string;
            } else {
                $rows[$key]->webpage_url_public = $value->website_url;
            }
        }

        return $rows;
    }

    /**
     * Add author usernames to the rows based on the 'created_by' values.
     *
     * @param array $rows The array of rows to modify.
     *
     * @return array The modified array of rows with added author usernames.
     */
    private function add_author_usernames(array $rows): array {
        $created_by_values = [];
        foreach ($rows as $row) {
            $created_by_values[] = (int) $row->created_by;
        }

        if (count($created_by_values) === 0) {
            return $rows;
        }

        // Use the retrieved 'created_by' values to execute a separate query to retrieve the author usernames
        $sql = "SELECT trongate_user_id, username FROM trongate_administrators WHERE trongate_user_id IN (" . implode(",", $created_by_values) . ")";
        $user_rows = $this->model->query($sql, 'object');
        $all_authors = [];
        foreach ($user_rows as $user_row) {
            $all_authors[$user_row->trongate_user_id] = $user_row->username;
        }

        foreach ($rows as $row_key => $row) {
            $created_by = $row->created_by;
            if (isset($all_authors[$created_by])) {
                $rows[$row_key]->author =  $all_authors[$created_by];
            } else {
                $rows[$row_key]->author = 'Unknown';
            }
        }

        return $rows;
    }

    /**
     * Returns an array of directories within a given directory
     * @param string $directory_name
     * @param bool|null $include_el_type Should type (i.e. 'directory') be included in the return array?
     *
     * @return array
     */
    private function fetch_directories(string $directory_name, bool|null $include_el_type = null): array {
        $directory_path = APPPATH . $directory_name;
        $directories = [];

        if (is_dir($directory_path)) {
            $files = scandir($directory_path);

            foreach ($files as $file) {
                $filePath = $directory_path . '/' . $file;

                if (is_dir($filePath) && !in_array($file, ['.', '..'])) {
                    $directories[] = $file;
                }
            }

            sort($directories);
        }

        if (isset($include_el_type) && (count($directories) > 0)) {
            $found_directories = [];
            foreach ($directories as $directory) {
                $row_data['info'] = $directory;
                $row_data['type'] = 'directory';
                $found_directories[] = $row_data;
            }
            return $found_directories;
        } else {
            return $directories;
        }
    }

    /**
     * Returns an array of images within a given directory
     * @param string $directory_name
     * @param bool|null $include_el_type Should type (i.e. 'image') be included in the return array?
     *
     * @return array
     */
    private function get_images_in_directory(string $directory_name, bool|null $include_el_type = null): array {
        $directory_name = rtrim($directory_name, "/");
        $directory_path = APPPATH . $directory_name;
        $img_root_url = BASE_URL . 'trongate_pages_module/' . $directory_name;
        $ditch = 'trongate_pages_module/modules/trongate_pages/assets/';
        $replace = 'trongate_pages_module/';
        $img_root_url = str_replace($ditch, $replace, $img_root_url);

        $images = [];
        if (is_dir($directory_path)) {
            $files = scandir($directory_path);
            foreach ($files as $file) {
                $file_path = $directory_path . '/' . $file;
                if (is_file($file_path) && in_array(pathinfo($file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = [
                        'file_name' => $file,
                        'date_uploaded' => filemtime($file_path),
                        'file_size' => filesize($file_path),
                        'url' => $img_root_url . '/' . $file
                    ];
                }
            }

            // Sort images alphabetically by 'file_name'
            usort($images, function ($a, $b) {
                return strcasecmp($a['file_name'], $b['file_name']);
            });
        }

        if (isset($include_el_type) && (count($images) > 0)) {
            $found_images = [];
            foreach ($images as $image) {
                $row_data['info'] = $image;
                $row_data['type'] = 'image';
                $found_images[] = $row_data;
            }
            return $found_images;
        } else {
            return $images;
        }
    }

    /**
     * Get the number of records selected per page.
     *
     * @return int The number of records selected per page.
     */
    private function get_selected_per_page(): int {
        $selected_per_page = (isset($_SESSION['selected_per_page'])) ? $_SESSION['selected_per_page'] : 1;
        return $selected_per_page;
    }

    /**
     * Check if a module exists that shares the same name as a target URL string.
     *
     * @param string $url_string The name of the URL string to check.
     * @return bool True if the module exists with the same name, false otherwise.
     */
    private function module_exists(string $url_string): bool {
        $module_path = APPPATH . 'modules/' . $url_string;
        if ($this->file->exists($module_path)) {
            return true;
        } else {
            return false;
        }
    }

}