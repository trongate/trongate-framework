<?php
class Site_builder extends Trongate {

    // Comment out this line for real operation. Uncomment and set to one of:
    //   'success' | 'access_denied' | 'permissions_error' | 'temp_dir_error' | 'views_dir_error'
    // private $simulate = 'success';

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'headline' => 'Access Denied',
                'message' => 'Module generation is only available when environment is in dev mode.',
                'more_info_url' => 'https://trongate.io/troubleshooting/dev-mode'
            ]);
            die();
        }
    }

    public function submit_generate_mod() {
    	
    	// Make sure PHP has permissions to generate modules.
    	$factory_path = APPPATH . 'modules/trongate_control/site_builder/factory';
	        if (!$this->model->check_write_permissions([$factory_path])) {
	            http_response_code(500);
	            header('Content-Type: application/json');
	            echo json_encode([
	                'status' => 'error',
	                'headline' => 'Permissions Error',
	                'message' => 'Module could not be created because of insufficient file permissions.',
	                'more_info_url' => 'https://trongate.io/troubleshooting/file-permissions'
	            ]);
	            die();
	        }

        // Clean up old temp folder records.
        $this->model->empty_directory($factory_path);

    	// Fetch the posted data.
    	$posted_values = post();
        $module_name = $posted_values['module_folder_name'] ?? '';

        if ($module_name === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'headline' => 'Invalid Request',
                'message' => 'Module could not be created because no module name was provided.'
            ]);
            die();
        }

        // Check that the module directory name is not already taken.
        $target_module_path = APPPATH . 'modules/' . $module_name;
        if (is_dir($target_module_path)) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'headline' => 'Module Already Exists',
                'message' => 'A module named \'' . $module_name . '\' already exists. Please choose a different module name or delete the existing module first.',
                'more_info_url' => 'https://trongate.io/troubleshooting/module-already-exists'
            ]);
            die();
        }

        // Check that the database table name is not already taken.
        $table_check_sql = 'SHOW TABLES LIKE :table_name';
        $existing_table = $this->db->query_bind($table_check_sql, ['table_name' => $module_name], 'object');
        if (!empty($existing_table)) {
            http_response_code(409);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'headline' => 'Database Table Already Exists',
                'message' => 'A database table named \'' . $module_name . '\' already exists. Please choose a different module name or drop the existing table first.',
                'more_info_url' => 'https://trongate.io/troubleshooting/table-already-exists'
            ]);
            die();
        }

        // If a navigation label was provided, verify that the admin template is writable.
        $nav_label = $posted_values['nav_label'] ?? '';
        if ($nav_label !== '') {
            $admin_template_path = APPPATH . 'modules/templates/views/admin.php';
            if (!is_writable($admin_template_path)) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'headline' => 'Permissions Error',
                    'message' => 'Module could not be created because the admin template file is not writable. A navigation link for this module cannot be added.',
                    'more_info_url' => 'https://trongate.io/troubleshooting/file-permissions'
                ]);
                die();
            }
        }

    	// Generate a token.
    	$token = time() . make_rand_str(16);

    	// Create a temp folder with the token name.
    	$temp_folder_path = $factory_path . '/' . $token;
	        if (!mkdir($temp_folder_path, 0777, true)) {
	            http_response_code(500);
	            header('Content-Type: application/json');
	            echo json_encode([
	                'status' => 'error',
	                'headline' => 'Directory Error',
	                'message' => 'Module could not be created because a temporary working directory could not be created.'
	            ]);
	            die();
	        }

	    // Generate the views directory and add to the temp folder.
    	$views_dir = $temp_folder_path . '/views';
	        if (!mkdir($views_dir, 0777)) {
	            http_response_code(500);
	            header('Content-Type: application/json');
	            echo json_encode([
	                'status' => 'error',
	                'headline' => 'Directory Error',
	                'message' => 'Module could not be created because a views directory could not be created.'
	            ]);
	            die();
	        }

    	// Generate the controller file and add to the temp folder.
    	$controller_class_name = ucfirst($module_name);
    	$controller_file_path = $temp_folder_path . '/' . $controller_class_name . '.php';

    	$model_file_path = $temp_folder_path . '/' . $controller_class_name . '_model.php';

        // Generate controller file content.
        $controller_content = $this->generate_controller_content($posted_values);
        file_put_contents($controller_file_path, $controller_content);
        chmod($controller_file_path, 0777);

        // Generate model file content.
        $model_content = $this->generate_model_content($posted_values);
        file_put_contents($model_file_path, $model_content);
        chmod($model_file_path, 0777);

        // Generate view files.
        $view_files = ['create', 'delete_conf', 'manage', 'not_found', 'show'];
        
        // Only generate search_modal if at least one property is searchable.
        $has_searchable = $this->model->has_searchable_property($posted_values['properties'] ?? '[]');
        if ($has_searchable) {
            $view_files[] = 'search_modal';
        }
        foreach ($view_files as $view_file) {
            $view_content = $this->generate_view_content($view_file, $posted_values);
            $view_file_path = $views_dir . '/' . $view_file . '.php';
            file_put_contents($view_file_path, $view_content);
            chmod($view_file_path, 0777);
        }

    	// Generate the SQL file and save it inside the module for import via Trongate Control.
        $sql = $this->generate_table_sql($posted_values);
        $sql_file_path = $temp_folder_path . '/' . $module_name . '.sql';
        file_put_contents($sql_file_path, $sql);
        chmod($sql_file_path, 0777);

    	// Move the temp folder into the 'modules' directory.
        $modules_path = APPPATH . 'modules';
        $final_path = $modules_path . '/' . $module_name;

        if (!rename($temp_folder_path, $final_path)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'headline' => 'Directory Error',
                'message' => 'Module could not be created because the generated files could not be moved into the modules directory.'
            ]);
            die();
        }

        // Ensure the module directory and all contents have generous permissions.
        chmod($final_path, 0777);
        $this->chmod_recursive($final_path, 0777);

        // Add a navigation menu link to the admin template if required.
        $this->model->add_nav_menu_link($module_name, $nav_label);

	    // Render the appropriate response based on $this->simulate.

        $simulate = (isset($this->simulate)) ? $this->simulate : '';

        switch ($simulate) {
            case 'access_denied':
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'headline' => 'Access Denied',
                    'message' => 'Module generation is only available when environment is in dev mode.',
                    'more_info_url' => 'https://trongate.io/troubleshooting/dev-mode'
                ]);
                break;

            case 'permissions_error':
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'headline' => 'Permissions Error',
                    'message' => 'Module could not be created because of insufficient file permissions.',
                    'more_info_url' => 'https://trongate.io/troubleshooting/file-permissions'
                ]);
                break;

            case 'temp_dir_error':
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'headline' => 'Directory Error',
                    'message' => 'Module could not be created because a temporary working directory could not be created.'
                ]);
                break;

            case 'views_dir_error':
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'headline' => 'Directory Error',
                    'message' => 'Module could not be created because a views directory could not be created.'
                ]);
                break;

            case 'success':
                // Simulated success.
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Module generated successfully.',
                    'redirect_url' => BASE_URL . 'trongate_control-site_builder/module_created/' . $module_name
                ]);
                break;

            default:
                // Real operation — $simulate property is commented out.
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Module generated successfully.',
                    'redirect_url' => BASE_URL . 'trongate_control-site_builder/module_created/' . $module_name
                ]);
                break;
        }
    }

    public function module_created() {
        $module_name = segment(3);
        $data['module_url'] = BASE_URL.$module_name.'/manage';
        $this->view('module_created', $data);
    }

    private function generate_controller_content($data) {

    	/*
    	 * Expected POST data structure:
    	 *
    	 * tempParams            (string)  - JSON: {"previousAction":"submitProperties"}
    	 * module_folder_name    (string)  - e.g., "tasks"
    	 * orderBy               (string)  - e.g., "id"
    	 * nav_label             (string)  - e.g., "Manage Tasks"
    	 * action                (string)  - e.g., "closeSecondaryWindow"
    	 * record_name_plural    (string)  - e.g., "Tasks"
    	 * urlColumn             (string)  - e.g., "id"
    	 * properties            (string)  - JSON array of property objects, each with:
    	 *     - propertyName      (string)  - e.g., "Task Title"
    	 *     - propertyType      (string)  - e.g., "varchar"
    	 *     - onForm            (string)  - "yes" | "no"
    	 *     - isSearchable      (string)  - "yes" | "no"
    	 *     - onSummaryTable    (string)  - "yes" | "no"
    	 *     - validationRules   (array)   - e.g., ["required","min length[2]","max length[255]"]
    	 * record_name_singular  (string)  - e.g., "Task"
    	 * orderByOptions        (string)  - JSON array of sort options
    	 * icon_id               (string)  - icon identifier (often empty)
    	 * icon_code             (string)  - icon code (often empty)
    	 */
    
        $data['dynamic_validation_tests'] = $this->model->build_validation_tests($data);
        $data['dynamic_callback_methods'] = $this->model->build_dynamic_callback_methods($data);
        $data['controller_name'] = ucfirst($data['module_folder_name']);
        $data['has_searchable'] = $this->model->has_searchable_property($data['properties'] ?? '[]');
        $data['searchable_columns'] = $this->model->get_searchable_columns($data['properties'] ?? '[]');
        $donor_controller_content = $this->view('donor_controller', $data, true);
        $donor_controller_content = $this->model->prep_file_contents($donor_controller_content);
        return $donor_controller_content;
    }

    private function generate_model_content($data) {
        $data['model_name'] = ucfirst($data['module_folder_name']) . '_model';

        $data['has_searchable'] = $this->model->has_searchable_property($data['properties'] ?? '[]');
        $data['searchable_columns'] = $this->model->get_searchable_columns($data['properties'] ?? '[]');
        $data['dynamic_posted_data'] = $this->model->build_dynamic_posted_data($data);
        $data['dynamic_prepared_record'] = $this->model->build_dynamic_prepared_record($data);
        $data['dynamic_validation_methods'] = $this->model->build_dynamic_validation_methods($data);
        $donor_model_content = $this->view('donor_model', $data, true);
        $donor_model_content = $this->model->prep_file_contents($donor_model_content);
        return $donor_model_content;
    }

    private function generate_view_content($view_file, $data) {

        // Add dynamic content based on view type
        if ($view_file === 'create') {
            $data['dynamic_form_fields'] = $this->model->build_dynamic_form_fields($data);
            $data['dynamic_date_constraint_js'] = $this->model->build_dynamic_date_constraint_js($data);
        } elseif ($view_file === 'manage') {
            $data['dynamic_table'] = $this->generate_dynamic_table($data);
        } elseif ($view_file === 'show') {
            $data['dynamic_details'] = $this->generate_dynamic_details($data);
        }

        $donor_view_content = $this->view('donor_' . $view_file, $data, true);
        $donor_view_content = $this->model->prep_file_contents($donor_view_content);
        return $donor_view_content;
    }

    private function generate_dynamic_table($data) {
        $properties = json_decode($data['properties']);
        $posted_properties = $this->model->est_posted_properties($properties);
        $data['properties'] = $this->model->return_table_properties($posted_properties);
        $table_code = $this->view('dynamic_table', $data, true);
        return $table_code;
    }

    private function generate_dynamic_details($data) {
        $properties = json_decode($data['properties']);
        $posted_properties = $this->model->est_posted_properties($properties);
        $data['properties'] = $posted_properties;
        $record_details_code = $this->view('dynamic_details', $data, true);
        return $record_details_code;        
    }

    private function generate_table_sql(array $posted_values): string {
        $module_name = $posted_values['module_folder_name'] ?? 'unknown';
        $properties = json_decode($posted_values['properties'] ?? '[]');

        $columns = [];
        $columns[] = '    id INT PRIMARY KEY AUTO_INCREMENT';

        foreach ($properties as $property) {
            $property_type = trim($property->propertyType ?? '');
            $property_name = $property->propertyName ?? '';
            $rules = $property->validationRules ?? [];
            $is_required = in_array('required', $rules);

            if ($property_type === 'date range') {
                $base_field = str_replace('-', '_', url_title($property_name));
                $start_required = in_array('start date required', $rules);
                $end_required = in_array('end date required', $rules);
                $columns[] = '    ' . $base_field . '_start DATE' . ($start_required ? ' NOT NULL' : ' DEFAULT NULL');
                $columns[] = '    ' . $base_field . '_end DATE' . ($end_required ? ' NOT NULL' : ' DEFAULT NULL');
                continue;
            }

            if ($property_type === 'time range') {
                $base_field = str_replace('-', '_', url_title($property_name));
                $start_required = in_array('start time required', $rules);
                $end_required = in_array('end time required', $rules);
                $columns[] = '    ' . $base_field . '_start TIME' . ($start_required ? ' NOT NULL' : ' DEFAULT NULL');
                $columns[] = '    ' . $base_field . '_end TIME' . ($end_required ? ' NOT NULL' : ' DEFAULT NULL');
                continue;
            }

            $field_name = str_replace('-', '_', url_title($property_name));
            $col_type = $this->map_property_type_to_sql($property_type, $rules);
            $null_clause = $is_required ? ' NOT NULL' : ' DEFAULT NULL';
            $columns[] = '    ' . $field_name . ' ' . $col_type . $null_clause;
        }

        $sql = 'CREATE TABLE ' . $module_name . ' (' . PHP_EOL;
        $sql .= implode(',' . PHP_EOL, $columns);
        $sql .= PHP_EOL . ');' . PHP_EOL;

        return $sql;
    }

    private function map_property_type_to_sql(string $property_type, array $rules): string {
        switch ($property_type) {
            case 'varchar':
                $max_length = $this->extract_max_length($rules);
                return 'VARCHAR(' . $max_length . ')';
            case 'textarea':
                return 'TEXT';
            case 'int':
                return 'INT';
            case 'decimal':
                return 'DECIMAL(10,2)';
            case 'boolean':
                return 'TINYINT(1)';
            case 'email':
                return 'VARCHAR(255)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'date and time':
                return 'DATETIME';
            default:
                return 'VARCHAR(255)';
        }
    }

    private function extract_max_length(array $rules): int {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'max length[')) {
                $value = extract_content($rule, '[', ']');
                if ($value !== '' && is_numeric($value)) {
                    return (int) $value;
                }
            }
        }
        return 255;
    }

    /**
     * Recursively set permissions on a directory and all its contents.
     *
     * @param string $dir  Directory path.
     * @param int    $mode Permission mode (e.g., 0777).
     * @return void
     */
    private function chmod_recursive(string $dir, int $mode): void {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                chmod($path, $mode);
                $this->chmod_recursive($path, $mode);
            } else {
                chmod($path, $mode);
            }
        }
    }

}
