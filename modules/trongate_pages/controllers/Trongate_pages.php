<?php

declare(strict_types=1);

class Trongate_pages extends Trongate
{
    private $entity_name_singular = 'Webpage';

    private $entity_name_plural = 'Webpages';

    private $max_allowed_levels = 7;

    private $default_limit = 20;

    private $per_page_options = [10, 20, 50, 100];

    private $page_template = 'public';

    private $admin_template = 'admin';

    private $max_file_size_mb = 5; // maximum allowed file size (for images) in megabytes

    private $max_width = 4200; // maximum allowed width for image uploads

    private $max_height = 3200; // set maximum allowed height for image uploads

    private $sample_text = 'Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis. Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit. Sit sint perferendis a totam repellendus vitae architecto sunt obcaecati doloribus deserunt, unde, molestiae maxime. Enim adipisci officiis sit. Quasi, aliquam, facilis.Lorem ipsum, dolor sit amet, consectetur adipisicing elit.';

    public function attempt_display(): void
    {
        $this_current_url = rtrim(current_url(), '/');
        $url_bits = explode('/', $this_current_url);

        $target_segment = end($url_bits);
        if ($target_segment === 'edit') {
            $num_bits = count($url_bits);
            $target_segment = $url_bits[$num_bits - 2];
        }

        $target_segment = str_replace('/', '', $target_segment);
        $record_obj = $this->model->get_one_where('url_string', $target_segment);

        if ($record_obj === false) {
            $this->template('error_404', []);

            return;
        }

        $data = (array) $record_obj;
        $data['targetTable'] = 'trongate_pages';
        $data['recordId'] = $record_obj->id;
        $data['imgUploadApi'] = BASE_URL.'trongate_pages/submit_image_upload';

        $last_segment = end($url_bits);

        //is this user an 'admin' user?
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token(1);
        $data['enable_page_edit'] = false;

        if (($last_segment === 'edit') && ($token !== false)) {
            $data['enable_page_edit'] = true;
        } elseif (($last_segment === 'edit') && ($token === false)) {
            if (strtolower(ENV) === 'dev') {
                redirect('trongate_pages/manage');
            }
        }

        if (($data['published'] === 0) && ($last_segment !== 'edit')) {
            //page not published
            load('error_404');
            exit;
        }

        $data['sample_text'] = $this->sample_text;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'display';
        $this->template($this->page_template, $data);
    }

    /**
     * Generate a unique URL string by concatenating search-friendly strings.
     *
     * @param  string  $temp_url_string The temp URL string.
     * @param  array  $all_website_pages An array of website page objects with 'url_string' property.
     * @param  int  $record_id The ID of the trongate_pages record for which we are creating a unique string.
     *
     * @return string The unique URL string.
     */
    public function _make_url_string_unique(string $temp_url_string, array $all_website_pages, int $record_id = 0): string
    {
        $got_matches = $this->_got_matches($temp_url_string, $all_website_pages, $record_id);

        if ($got_matches === false) {
            return $temp_url_string; // URL string is unique - no need to go any further
        }

        $search_friendly_strings = [
            'information',
            'advice',
            'details',
            'insights',
            'data',
            'tips',
            'facts',
            'knowledge',
            'solutions',
            'resources',
            'overview',
            'explanation',
            'learn-more',
            'deep-dive',
            'how-to',
            'best-practices',
            'explore',
            'in-depth',
            'essentials',
            'analysis',
            'research',
            'walk-through',
        ];

        $unique_url_string = $temp_url_string;

        while ($got_matches === true) {
            $random_key = array_rand($search_friendly_strings);
            $random_string = $search_friendly_strings[$random_key];
            $unique_url_string .= '-'.$random_string;

            $got_matches = $this->_got_matches($unique_url_string, $all_website_pages, $record_id);
        }

        return $unique_url_string;
    }

    public function _got_matches($temp_url_string, $all_website_pages, $record_id)
    {
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
        }
        return false;
    }

    /**
     * Retrieve suggestions for records based on the provided id.
     *
     * @return array Returns an array of objects representing the suggested records.
     */
    public function suggest(): array
    {
        //api_auth();
        $params = file_get_contents('php://input');
        $posted_data = (object) json_decode($params);

        if (! isset($posted_data->page_title)) {
            http_response_code(400);
            exit;
        }

        $args['page_title'] = $posted_data->page_title.'%';
        $sql = 'SELECT id, page_title, url_string FROM trongate_pages WHERE page_title LIKE :page_title';
        $rows = $this->model->query_bind($sql, $args, 'object');

        // Add a suggest string to each of the rows
        foreach ($rows as $key => $value) {
            $page_title = $value->page_title;
            $rows[$key]->suggest_str = $page_title.' (page ID: '.$value->id.')';
        }

        http_response_code(200);
        echo json_encode($rows);

        return $rows;
    }

    /**
     * View pages in 'Category Builder' mode/
     */
    public function category_builder(): void
    {
        $this->module('trongate_security');
        $data['token'] = $this->trongate_security->_make_sure_allowed();
        $data['all_pages'] = $this->model->get('id', 'trongate_pages');
        $data['entity_name_singular'] = $this->entity_name_singular;
        $data['entity_name_plural'] = $this->entity_name_plural;
        $data['max_allowed_levels'] = $this->max_allowed_levels;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'category_builder';
        $this->template('admin', $data);
    }

    /**
     * View pages in 'List View' mode/
     */
    public function list_view(): void
    {
        $sql = 'SELECT * FROM trongate_pages ORDER BY parent_page_id, priority';
        $data['rows'] = $this->model->query($sql, 'object');

        $this->module('trongate_security');
        $data['token'] = $this->trongate_security->_make_sure_allowed();
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'trongate_pages_list_view';
        $this->template('admin', $data);
    }

    /**
     * Redirect to manage page if user visits trongate_pages/
     */
    public function index(): void
    {
        redirect('trongate_pages/manage');
    }

    /**
     * Ensures that the current user is allowed to access the protected resource.
     * Feel free to change to suit your own individual use case.
     *
     * @return string|false The security token if the user is authorized, or false otherwise.
     */
    public function _make_sure_allowed(): string|false
    {
        //by default, 'admin' users (i.e., users with user_level_id === 1) are allowed
        $this->module('trongate_security');
        return $this->trongate_security->_make_sure_allowed();
    }

    /**
     * Executes a before hook that updates the input data and sets the 'last_updated' parameter to the current time.
     *
     * @param  array  $input The input data to be updated.
     *
     * @return array The updated input data.
     */
    public function _pre_update(array $input): array
    {
        if (isset($input['params']['page_title'])) {
            $page_title = trim($input['params']['page_title']);

            if ($page_title === '') {
                http_response_code(400);
                echo 'Invalid page title!';
                exit;
            }

            $url_string = strtolower(url_title($page_title));
            $input['params']['url_string'] = $url_string;
            $update_id = (int) segment(4);
            if ($update_id === 0) {
                $input['params']['parent_page_id'] = 0;
                $input['params']['priority'] = 0;
            }
        }

        $input['params']['last_updated'] = time();

        return $input;
    }

    /**
     * Fetch the uploaded images.
     */
    public function fetch_uploaded_images(): void
    {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo 'Invalid inbound JSON.';
            exit;
        }
        $current_img_dir = $data->currentImgDir ?? '';

        $directory_name = 'modules/trongate_pages/assets/images/uploads';

        if ($current_img_dir !== '') {
            $directory_name .= trim($current_img_dir);
        }

        $directories = $this->_fetch_directories($directory_name, true);
        $images = $this->_get_images_in_directory($directory_name, true);

        if (count($directories) > 0) {
            $images = array_merge($directories, $images);
        }

        http_response_code(200);
        echo json_encode($images);
    }

    /**
     * Returns an array of directories within a given directory
     *
     * @param  bool|null  $include_el_type Should type (i.e. 'directory') be included in the return array?
     */
    public function _fetch_directories(string $directory_name, ?bool $include_el_type = null): array
    {
        $directory_path = APPPATH.$directory_name;
        $directories = [];

        if (is_dir($directory_path)) {
            $files = scandir($directory_path);

            foreach ($files as $file) {
                $filePath = $directory_path.'/'.$file;

                if (is_dir($filePath) && ! in_array($file, ['.', '..'])) {
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
        }
        return $directories;
    }

    /**
     * Returns an array of images within a given directory
     *
     * @param  bool|null  $include_el_type Should type (i.e. 'image') be included in the return array?
     */
    public function _get_images_in_directory(string $directory_name, ?bool $include_el_type = null): array
    {
        $directory_name = rtrim($directory_name, '/');
        $directory_path = APPPATH.$directory_name;
        $img_root_url = BASE_URL.'trongate_pages_module/'.$directory_name;
        $ditch = 'trongate_pages_module/modules/trongate_pages/assets/';
        $replace = 'trongate_pages_module/';
        $img_root_url = str_replace($ditch, $replace, $img_root_url);

        $images = [];
        if (is_dir($directory_path)) {
            $files = scandir($directory_path);
            foreach ($files as $file) {
                $file_path = $directory_path.'/'.$file;
                if (is_file($file_path) && in_array(pathinfo($file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = [
                        'file_name' => $file,
                        'date_uploaded' => filemtime($file_path),
                        'file_size' => filesize($file_path),
                        'url' => $img_root_url.'/'.$file,
                    ];
                }
            }

            // Sort images alphabetically by 'file_name'
            usort($images, static function ($a, $b) {
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
        }
        return $images;
    }

    /**
     * Displays a list of pages that can be managed.
     */
    public function manage(): void
    {
        $token = $this->_make_sure_allowed();

        // Check if the image folder exists and is writable
        $folder_path = APPPATH.'modules/trongate_pages/assets/images/uploads';
        if (! is_writable($folder_path)) {
            $data['view_module'] = 'trongate_pages';
            $this->view('permissions_error', $data);
            exit;
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
            $data['headline'] = 'Manage Webpages';
            $all_rows = $this->model->get('date_created desc');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'trongate_pages/manage';
        $pagination_data['record_name_plural'] = 'webpages';
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
     */
    public function display(): void
    {
        $target_segment = segment(2) !== 'display' ? 2 : 3;
        $record_obj = $this->model->get_one_where('url_string', segment($target_segment));
        $data = (array) $record_obj;
        $data['targetTable'] = 'trongate_pages';
        $data['recordId'] = $record_obj->id;
        $data['imgUploadApi'] = BASE_URL.'trongate_pages/submit_image_upload';

        $last_segment = SEGMENTS[count(SEGMENTS) - 1];

        //is this user an 'admin' user?
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token(1);
        $data['enable_page_edit'] = false;

        if (($last_segment === 'edit') && ($token !== false)) {
            $data['enable_page_edit'] = true;
        } elseif (($last_segment === 'edit') && ($token === false)) {
            if (strtolower(ENV) === 'dev') {
                redirect('trongate_pages/manage');
            }
        }

        if (($data['published'] === 0) && ($last_segment !== 'edit')) {
            //page not published
            load('error_404');

            if (strtolower(ENV) === 'dev') {
                $this->view('not_published_info');
            }

            exit;
        }

        $data['sample_text'] = $this->sample_text;
        $data['view_module'] = 'trongate_pages';
        $data['view_file'] = 'display';
        $this->template($this->page_template, $data);
    }

    /**
     * Attempt to active page edit functionality for the given Trongate pages data.
     *
     * @param  array  $trongate_pages_data An associative array of Trongate pages data.
     */
    public function _attempt_enable_page_edit(array $trongate_pages_data): void
    {
        $enable_page_edit = $trongate_pages_data['enable_page_edit'] ?? false;

        if ($enable_page_edit === true) {
            $this->module('trongate_tokens');
            $trongate_pages_data['trongate_token'] = $this->trongate_tokens->_attempt_get_valid_token();
            $this->view('enable_page_edit', $trongate_pages_data);
        }
    }

    /**
     * Accepts an array of records and reduces array size,
     * based on current $limit and $offset values.
     */
    public function _reduce_rows(array $all_rows): array
    {
        $rows = [];
        $start_index = $this->_get_offset();
        $limit = $this->_get_limit();
        $end_index = $start_index + $limit;
        $webpage_trigger = defined('TRONGATE_PAGES_TRIGGER') ? TRONGATE_PAGES_TRIGGER : 'trongate_pages/display';

        $count = -1;
        foreach ($all_rows as $webpage) {
            $count++;
            if (($count >= $start_index) && ($count < $end_index)) {
                $webpage->published = ($webpage->published === 1 ? 'yes' : 'no');
                $webpage->webpage_url = BASE_URL.$webpage_trigger.'/'.$webpage->url_string;
                $rows[] = $webpage;
            }
        }

        return $rows;
    }

    /**
     * Submit page title & create new database record.
     */
    public function submit(): void
    {
        $trongate_token = $this->_make_sure_allowed();

        $this->module('trongate_tokens');
        $trongate_user_id = $this->trongate_tokens->_get_user_id();

        $this->validation_helper->set_rules('page_title', 'page title', 'required|min_length[2]|callback_title_check');
        $result = $this->validation_helper->run();

        if ($result === true) {
            $data['page_title'] = post('page_title', true);
            $data['meta_keywords'] = '';
            $data['meta_description'] = '';
            $data['page_body'] = '<h1>'.$data['page_title'].'</h1>';
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

    //this gets called from the navigation builder at navigation_menus/build/1
    public function submit_create_page(): void
    {
        $posted_data = file_get_contents('php://input');
        $posted_data_decoded = json_decode($posted_data);

        $page_title = $posted_data_decoded->pageTitle ?? '';

        $data['page_title'] = $page_title;
        $data['meta_keywords'] = '';
        $data['meta_description'] = '';
        $data['page_body'] = '<h1>'.$page_title.'</h1>';
        $data['date_created'] = time();
        $data['last_updated'] = time();
        $data['published'] = 1;
        $data['created_by'] = 1;
        $data['url_string'] = $this->_make_url_str($page_title);
        $update_id = $this->model->insert($data, 'trongate_pages');
        http_response_code(200);
        echo $update_id;
    }

    public function submit_delete(): void
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = (int) segment(3);

        if (($submit === 'Yes - Delete Now') && ($params['update_id'] > 0)) {
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
     * Create new folder.
     */
    public function submit_create_new_img_folder(): void
    {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            exit;
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
        $root_dir_path = APPPATH.'modules/trongate_pages/assets/images/uploads/'.trim($current_img_dir);

        if (! is_dir($root_dir_path)) {
            http_response_code(400);
            echo 'Invalid root dir!';
            exit;
        }

        $root_dir_path = rtrim($root_dir_path, '/');

        // Make sure new folder does not already exist.
        $new_folder_path = $root_dir_path.'/'.$folder_name;
        $new_folder_path = rtrim($new_folder_path, '/');

        if (is_dir($new_folder_path)) {
            http_response_code(400);
            echo 'Folder exists!';
            exit;
        }

        try {
            // Create the folder
            if (! file_exists($new_folder_path)) {
                mkdir($new_folder_path, 0755, true);
            }

            // Create an empty 'index.php' file inside the folder
            $index_file_path = $new_folder_path.'/index.php';
            if (! file_exists($index_file_path)) {
                file_put_contents($index_file_path, '');
            }

            // Set the folder permissions to 755
            chmod($new_folder_path, 0755);
            http_response_code(200);
            echo $new_folder_path;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'An error occurred: '.$e->getMessage();
            exit;
        }
    }

    /**
     * Rename an existing folder.
     */
    public function submit_rename_img_folder(): void
    {
        api_auth();
        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            exit;
        }

        $old_folder_name = $data->oldFolderName ?? '';
        $new_folder_name = $data->newFolderName ?? '';
        $current_img_dir = $data->currentImgDir ?? '';

        // Ensure the new folder name contains only safe characters
        $new_folder_name = preg_replace('/[^a-zA-Z0-9-_]/', '', $new_folder_name);

        if (empty($new_folder_name)) {
            http_response_code(400);
            echo 'New folder name contains invalid characters or is empty.';
            exit;
        }

        $old_folder_path = APPPATH.'modules/trongate_pages/assets/images/uploads'.trim($current_img_dir).'/'.$old_folder_name;
        $new_folder_path = APPPATH.'modules/trongate_pages/assets/images/uploads'.trim($current_img_dir).'/'.$new_folder_name;

        // Check if the old folder exists
        if (! is_dir($old_folder_path)) {
            http_response_code(400);
            echo 'Old folder does not exist!';
            exit;
        }

        // Make sure new folder does not already exist.
        if (is_dir($new_folder_path)) {
            http_response_code(400);
            echo 'New folder already exists!';
            exit;
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
            echo 'An error occurred: '.$e->getMessage();
            exit;
        }
    }

    /**
     * Handles the submission of an image upload and saves the file to the server.
     *
     * @throws Exception If no file was submitted
     */
    public function submit_image_upload(): void
    {
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

        if (! in_array('trongate_pages', $all_tables)) {
            http_response_code(400);

            $error_msg = strtolower(ENV) === 'dev' ? 'trongate_pages table not found!' : 'Invalid request!';
            echo $error_msg;
            exit;
        }

        try {
            if (isset($_FILES['file1'])) {
                $fileName = $_FILES['file1']['name']; // The file name
                $fileTmpLoc = $_FILES['file1']['tmp_name']; // File in the PHP tmp folder
                $fileType = $_FILES['file1']['type']; // The type of file it is
                $fileSize = $_FILES['file1']['size']; // File size in bytes
                $fileErrorMsg = $_FILES['file1']['error']; // 0 for false... and 1 for true
            } else {
                http_response_code(400);
                echo 'No file was submitted';
                exit;
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo 'Error: '.$e->getMessage();
            exit;
        }

        $destination_dir = '../modules/trongate_pages/assets/images/uploads';

        if ($current_img_dir !== '') {
            $destination_dir .= $current_img_dir;
        }

        if (! $fileTmpLoc) { // if file not chosen
            http_response_code(400);
            echo 'Please browse for a file before clicking the upload button.';
            exit;
        }

        $max_file_size_mb = $this->max_file_size_mb; // set maximum allowed file size in megabytes
        $max_width = $this->max_width; // set maximum allowed width
        $max_height = $this->max_height; // set maximum allowed height

        $file_validation_result = $this->validate_file($fileTmpLoc, $max_file_size_mb, $max_width, $max_height);

        if ($file_validation_result['error']) {
            http_response_code(400);
            echo $file_validation_result['message'];
            exit;
        }

        $fileName = $this->prep_file_name($fileName, $destination_dir); // guarantee that file names are not problematic

        if (move_uploaded_file($fileTmpLoc, $destination_dir.'/'.$fileName)) {
            http_response_code(200);
            $destination_dir = str_replace('../', '/', $destination_dir);
            $abs_path1 = BASE_URL.'trongate_pages_module/';
            $replace = $destination_dir.'/'.$fileName;
            $picture_path = str_replace('/modules/trongate_pages/assets/', $abs_path1, $replace);
            echo $picture_path;
            exit;
        }
        http_response_code(400);
        echo 'Unable to upload to: '.$destination_dir.'/'.$fileName;
        exit;
        echo 'move_uploaded_file function failed';
        exit;
    }

    /**
     * Deletes an image from the server.
     */
    public function submit_delete_image(): void
    {
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

            $directory_path = APPPATH.$directory_name;
            $file_path = $directory_path.'/'.$file_name;

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
     * Deletes a folder from the server.
     */
    public function submit_delete_folder(): void
    {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        $data_type = gettype($data);
        if ($data_type !== 'object') {
            http_response_code(400);
            echo 'Invalid inbound data.';
            exit;
        }

        $submitted_folder_name = $data->folderName ?? '';
        $current_img_dir = $data->currentImgDir ?? '';

        // Check if the current img dir exists
        $root_dir_path = APPPATH.'modules/trongate_pages/assets/images/uploads/';

        if ($current_img_dir !== '') {
            $current_img_dir = ltrim($current_img_dir, '/');
            $root_dir_path .= $current_img_dir;
        }

        if (! is_dir($root_dir_path)) {
            http_response_code(400);
            echo 'Invalid root dir!';
            exit;
        }

        // Make sure folder exists.
        $target_folder_path = $root_dir_path.'/'.$submitted_folder_name;

        if (! is_dir($target_folder_path)) {
            http_response_code(400);
            echo 'Folder does not exist!';
            exit;
        }

        $this->delete_directory($target_folder_path);
        http_response_code(200);
    }

    /**
     * Recursively deletes a directory and all its contents.
     *
     * @param  string  $target_folder_path The path of the target folder to delete.
     *
     * @throws Exception If an error occurs while deleting the directory.
     */
    public function delete_directory(string $target_folder_path): void
    {
        try {
            if (! is_dir($target_folder_path)) {
                return;
            }
            $files = scandir($target_folder_path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $file_path = $target_folder_path.'/'.$file;
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
            echo 'Error deleting directory: '.$e->getMessage();
        }
    }

    /**
     * Submit and beautify HTML content.
     */
    public function submit_beautify(): void
    {
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
     * @param  string  $content The HTML content to beautify.
     * @param  string  $tab The tab character for indentation.
     *
     * @return string The beautified HTML content.
     */
    public function beautify_html(string $content, string $tab = "\t"): string
    {
        $content = str_replace('  ', ' ', $content);
        $content = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $content);
        $token = strtok($content, "\n");
        $result = '';
        $pad = 0;
        $indent = 0;
        $matches = [];
        $voidTag = false; // Initialize voidTag variable
        while ($token !== false && strlen($token) > 0) {
            $padPrev = $padPrev ?? $pad;
            $token = trim($token);
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) {
                $indent = 0;
            } elseif (preg_match('/^<\/\w/', $token, $matches)) {
                $pad--;
                if ($indent > 0) {
                    $indent = 0;
                }
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
            if ($token === '<textarea>') {
                $line = str_pad($token, strlen($token) + $pad, $tab, STR_PAD_LEFT);
                $result .= $line;
                $token = strtok("\n");
                $pad += $indent;
            } elseif ($token === '</textarea>') {
                $line = $token;
                $result .= $line."\n";
                $token = strtok("\n");
                $pad += $indent;
            } else {
                $line = str_pad($token, strlen($token) + $pad, $tab, STR_PAD_LEFT);
                $result .= $line."\n";
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
     */
    public function check_youtube_video_id(): void
    {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $data = json_decode($posted_data);

        if (! is_object($data)) {
            http_response_code(400); // Bad request
            exit;
        }

        $video_id = isset($data->video_id) ? trim($data->video_id) : '';

        // Regular expression pattern to match YouTube video ID and exclude additional variables
        $pattern = '/^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:embed\/|watch\?v=|v\/|watch\?.+&v=|watch\/.+\/\?)|youtu\.be\/)([^&\?\/\s]+)/';

        // Check if the provided string matches the pattern
        if (preg_match($pattern, $video_id, $matches)) {
            $video_id_len = strlen($matches[1]);

            if ($video_id_len !== 11) {
                http_response_code(406); // Not acceptable
                exit;
            }
            http_response_code(200);
            echo $matches[1];
            exit;
        }

        // Check if the video ID is valid
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id)) {
            $video_id_len = strlen($video_id);

            if ($video_id_len !== 11) {
                http_response_code(406); // Not acceptable
                exit;
            }
            http_response_code(200);
            echo $video_id;
            exit;
        }

        http_response_code(406); // Not acceptable
    }

    public function _get_limit()
    {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    public function _get_offset()
    {
        $page_num = (int) segment(3);

        if ($page_num > 1) {
            $offset = ($page_num - 1) * $this->_get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    public function _get_selected_per_page()
    {
        return $_SESSION['selected_per_page'] ?? 1;
    }

    public function set_per_page($selected_index): void
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (! is_numeric($selected_index)) {
            $selected_index = $this->per_page_options[1];
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('trongate_pages/manage');
    }

    /**
     * Creates a URL string (slug) from the page title.
     *
     * @param  string  $page_title The page title.
     *
     * @return string Returns a URL string (slug) based on page title,
     *                      or random string if URL string is not unique.
     */
    public function _make_url_str(string $page_title): string
    {
        $url_string = url_title($page_title);
        $record_obj = $this->model->get_one_where('url_string', $url_string, 'trongate_pages');

        if ($record_obj !== false) {
            $url_string .= make_rand_str(8);
        }

        return $url_string;
    }

    /**
     * Add author usernames to the rows based on the 'created_by' values.
     *
     * @param  array  $rows The array of rows to modify.
     *
     * @return array The modified array of rows with added author usernames.
     */
    public function _add_author_usernames(array $rows): array
    {
        $created_by_values = [];
        foreach ($rows as $row) {
            $created_by_values[] = (int) $row->created_by;
        }

        if (count($created_by_values) === 0) {
            return $rows;
        }

        // Use the retrieved 'created_by' values to execute a separate query to retrieve the author usernames
        $sql = 'SELECT trongate_user_id, username FROM trongate_administrators WHERE trongate_user_id IN ('.implode(',', $created_by_values).')';
        $user_rows = $this->model->query($sql, 'object');
        $all_authors = [];
        foreach ($user_rows as $user_row) {
            $all_authors[$user_row->trongate_user_id] = $user_row->username;
        }

        foreach ($rows as $row_key => $row) {
            $created_by = $row->created_by;
            if (isset($all_authors[$created_by])) {
                $rows[$row_key]->author = $all_authors[$created_by];
            } else {
                $rows[$row_key]->author = 'Unknown';
            }
        }

        return $rows;
    }

    public function _get_data_from_db($update_id)
    {
        $record_obj = $this->model->get_where($update_id, 'trongate_pages');

        if ($record_obj === false) {
            $this->template('error_404');
            exit;
        }
        return (array) $record_obj;
    }

    public function _get_data_from_post()
    {
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
     * @param  string  $str The page title to be checked.
     *
     * @return string|bool Returns an error message string if the page title is not unique,
     *                      or true if the page title is unique.
     */
    public function title_check(string $str): string|bool
    {
        $page_title = trim(strip_tags($str));
        $page_title = preg_replace('/[ ]+/', ' ', $page_title);
        $charset = defined('CHARSET') ? CHARSET : 'UTF-8';
        $page_title = htmlspecialchars($page_title, ENT_QUOTES, $charset);

        //make sure page title is unique
        $record_obj = $this->model->get_one_where('page_title', $page_title, 'trongate_pages');
        if ($record_obj !== false) {
            return 'The page title that you submitted is not available.';
        }
        return true;
    }

    /**
     * Prepare the file name for uploading.
     *
     * @param  string  $original_file_name The original file name.
     * @param  string  $destination_dir The destination directory for the file.
     *
     * @return string The prepared file name.
     */
    public function prep_file_name(string $original_file_name, string $destination_dir): string
    {
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
        $file_path = $directory_name.'/'.$file_name.'.'.$file_extension;

        while (file_exists(APPPATH.$file_path)) {
            $i++;
            $file_path = $directory_name.'/'.$file_name.$i.'.'.$file_extension;
        }

        $safe_file_name = $i > 1 ? $file_name.$i.'.'.$file_extension : $file_name.'.'.$file_extension;

        return strtolower($safe_file_name);
    }

    /**
     * Validate the uploaded file based on size and dimensions.
     *
     * @param  string  $fileTmpLoc The temporary location of the uploaded file.
     * @param  int  $max_file_size_mb The maximum allowed file size in megabytes.
     * @param  int  $max_width The maximum allowed width for the image.
     * @param  int  $max_height The maximum allowed height for the image.
     *
     * @return array An array containing the validation result with 'error' and 'message' keys.
     */
    public function validate_file(string $fileTmpLoc, int $max_file_size_mb, int $max_width, int $max_height): array
    {
        $file_size_mb = round(filesize($fileTmpLoc) / 1048576, 2); // Calculate file size in megabytes
        $image_size = getimagesize($fileTmpLoc); // Get image size

        $validation_result = [
            'error' => false,
            'message' => '',
        ];

        // Check file size
        if ($file_size_mb > $max_file_size_mb) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded file exceeds the maximum allowed file size of '.$max_file_size_mb.'MB.';

            return $validation_result;
        }

        // Check image size
        $width = $image_size[0];
        $height = $image_size[1];

        if ($width > $max_width) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded image width exceeds the maximum allowed width of '.$max_width.'px.';

            return $validation_result;
        }

        if ($height > $max_height) {
            $validation_result['error'] = true;
            $validation_result['message'] = 'The uploaded image height exceeds the maximum allowed height of '.$max_height.'px.';

            return $validation_result;
        }

        return $validation_result;
    }

    /**
     * Draw an area that lets you drag and drop positions of pages.
     *
     * @param  array  $all_pages All records from the trongate_pages table.
     */
    public function _draw_dragzone_content(array $all_pages): void
    {
        $data['all_pages'] = $all_pages;
        $this->view('dragzone_content', $data);
    }

    /**
     * A before hook that creates a url_string, based on a submitted page title.
     *
     * @param  array  $all_pages All records from the trongate_pages table.
     */
    public function _pre_create($input)
    {
        $token = ($_SERVER['HTTP_TRONGATETOKEN'] ?? false);
        if ($token === false) {
            http_response_code(401);
            echo 'Not authorized';
            exit;
        }

        $this->module('trongate_tokens');
        $trongate_user_id = $this->trongate_tokens->_get_user_id($token);

        $page_title = trim($input['params']['page_title']);

        if ($page_title === '') {
            http_response_code(400);
            echo 'Invalid page title!';
            exit;
        }
        $url_string = strtolower(url_title($page_title));
        $input['params']['url_string'] = $url_string;

        $update_id = segment(4);

        if (! is_numeric($update_id)) {
            $input['params']['meta_keywords'] = '';
            $input['params']['meta_description'] = '';
            $input['params']['page_body'] = '';
            $input['params']['date_created'] = time();
            $input['params']['last_updated'] = time();
            $input['params']['published'] = 0;
            $input['params']['created_by'] = $trongate_user_id;
            $input['params']['parent_page_id'] = 0;

            //how many other records exist on this level?
            $num_siblings = $this->model->count_rows('parent_page_id', 0, 'trongate_pages');
            $input['params']['priority'] = $num_siblings + 1;
        }

        return $input;
    }

    /**
     * Prepare the ID by removing the 'record-id-' prefix and handling special cases.
     *
     * @param  string  $id The ID to be prepared.
     *
     * @return int|string The prepared ID as an integer or 'dragzone' as string.
     */
    public function _prep_id(string $id): int|string
    {
        $id = str_replace('record-id-', '', $id);

        if ($id === 'dragzone') {
            $id = 0;
        }

        return $id;
    }

    /**
     * Remember the positions by updating priority and parent_page_id in the database.
     */
    public function remember_positions(): void
    {
        api_auth();

        $posted_data = file_get_contents('php://input');
        $child_nodes = json_decode($posted_data);
        // echo '-----';
        // json($child_nodes); die();
        $sql = '';
        foreach ($child_nodes as $child_node) {
            $id = $this->_prep_id($child_node->id);
            $parent_webpage_id = $this->_prep_id($child_node->parent_webpage_id);
            $priority = $child_node->priority;

            if ((! is_numeric($id)) || (! is_numeric($parent_webpage_id)) || (! is_numeric($priority))) {
                exit;
            }

            $sql .= 'UPDATE trongate_pages SET priority = '.$priority.', parent_page_id = '.$parent_webpage_id.' WHERE id = '.$id.';';
        }

        if ($sql !== '') {
            $this->model->query($sql);
        }
    }

    /**
     * Send user to a (editable) webpage URL
     */
    public function goto(): void
    {
        $update_id = (int) segment(3);
        $webpage_url = $this->_make_page_url($update_id);
        redirect($webpage_url);
    }

    /**
     * Make a page URL for editing a specific record.
     *
     * @param  int  $update_id The ID of the record to be edited.
     * @param  bool  $return_edit_url should method return the 'edit' URL.
     *
     * @return string|false The page URL for editing the record, or false if the record doesn't exist.
     */
    public function _make_page_url(int $update_id, bool $return_edit_url = true): string|false
    {
        $record_obj = $this->model->get_where($update_id, 'trongate_pages');

        if ($record_obj === false) {
            return false;
        }
        $page_url = BASE_URL.'trongate_pages/display/'.$record_obj->url_string;
        if ($return_edit_url !== false) {
            $page_url .= '/edit';
        }

        return $page_url;
    }

    /**
     * Before hook for 'Delete One' operation to ensure delete is allowed.
     *
     * @param  array  $input The input data received for the operation.
     *
     * @return array The modified input data after ensuring delete is allowed.
     */
    public function _make_sure_delete_allowed(array $input): array
    {
        // Make sure no other trongate_pages have this as a parent
        $params['parent_page_id'] = $input['params']['id'];
        $sql = 'select * from trongate_pages where parent_page_id = :parent_page_id';
        $rows = $this->model->query_bind($sql, $params, 'array');

        if (count($rows) > 0) {
            http_response_code(400);
            echo 'At least one other page has this as a parent!';
            exit;
        }

        return $input;
    }
} //end of the class
