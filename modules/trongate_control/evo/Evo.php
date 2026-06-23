<?php
class Evo extends Trongate {

    public $api_base_url = 'https://trongate.io/';

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            $this->render_disabled_response();
            die();
        }
    }

    public function home(): void {
        $this->view('home');
    }

    public function module_manager(): void {
        $this->view('module_manager');
    }

    public function enter_mod_name(): void {
        // Clear any stale wizard session state when starting fresh
        unset($_SESSION['evo_wizard']);
        $this->view('enter_mod_name');
    }

    /**
     * Reset the wizard — clear session state and return to the main menu.
     */
    public function reset(): void {
        unset($_SESSION['evo_wizard']);
        $this->view('home');
    }

    // ============================================
    // Wizard Step 1: Submit Module Name
    // ============================================

    public function submit_mod_name(): void {
        $this->module('trongate_control-plural_maker');
        $mod_name = post('mod_name', true);

        // Validate minimum length
        if (strlen($mod_name) < 2) {
            echo $this->render_error('Module name must be at least 2 characters long.');
            return;
        }

        // Validate maximum length
        if (strlen($mod_name) > 50) {
            echo $this->render_error('Module name cannot exceed 50 characters.');
            return;
        }

        // Validate format (must start with a letter, alphanumeric + underscores + spaces)
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_ ]*$/', $mod_name)) {
            echo $this->render_error('Module name must start with a letter and contain only letters, numbers, underscores, and spaces.');
            return;
        }

        // Check for module assets trigger
        if (defined('MODULE_ASSETS_TRIGGER') && strpos($mod_name, MODULE_ASSETS_TRIGGER) !== false) {
            echo $this->render_error('Module name cannot contain "' . MODULE_ASSETS_TRIGGER . '".');
            return;
        }

        // Check for reserved names
        $reserved_names = ['config', 'system', 'engine', 'public', 'templates', 'assets', 'modules', 'welcome'];
        if (in_array(strtolower($mod_name), $reserved_names)) {
            echo $this->render_error('This module name is reserved and cannot be used.');
            return;
        }

        // Derive plural and folder name
        $plural = $this->plural_maker->make_plural($mod_name);
        $plural = ucwords($plural); // Title case for display ("Manage Cars", not "Manage cars")
        $module_folder_name = $this->string_service->url_title(['value' => $plural]);
        $module_folder_name = str_replace('-', '_', $module_folder_name);

        // Check for conflicts with existing modules
        $existing_modules = scandir(APPPATH . 'modules/');
        $existing_clean = array_filter($existing_modules, fn($d) => $d !== '.' && $d !== '..');
        if (in_array($module_folder_name, $existing_clean)) {
            echo $this->render_error("A module named '{$module_folder_name}' already exists.");
            return;
        }

        // Store in session
        $_SESSION['evo_wizard'] = [
            'record_name_singular' => $mod_name,
            'record_name_plural' => $plural,
            'module_folder_name' => $module_folder_name,
        ];

        // Show the next step: add nav label
        $data['view_module'] = 'trongate_control/evo';
        $data['record_name_plural'] = $plural;
        $this->view('add_nav_label', $data);
    }

    // ============================================
    // Wizard Step 2: Add Navigation Label
    // ============================================

    public function add_nav_label(): void {
        // Redirect back to start if no wizard session exists
        if (!isset($_SESSION['evo_wizard'])) {
            $this->enter_mod_name();
            return;
        }
        $plural = $_SESSION['evo_wizard']['record_name_plural'] ?? '';
        $data['view_module'] = 'trongate_control/evo';
        $data['record_name_plural'] = $plural;
        $this->view('add_nav_label', $data);
    }

    public function submit_nav_label(): void {
        $nav_label = post('nav_label', true);

        // Store in session
        $_SESSION['evo_wizard']['nav_label'] = $nav_label;
        $_SESSION['evo_wizard']['icon_code'] = '';
        $_SESSION['evo_wizard']['icon_id'] = '';

        // Show the next step: properties confirmation (stopping point)
        $data['view_module'] = 'trongate_control/evo';
        $this->view('lets_add_properties_conf', $data);
    }

    // ============================================
    // Wizard Step 3: Properties Confirmation (STOP)
    // ============================================

    public function lets_add_properties_conf(): void {
        $data['view_module'] = 'trongate_control/evo';
        $this->view('lets_add_properties_conf', $data);
    }

    // ============================================
    // Wizard Step 4: Store Properties (via AJAX from PB iframe)
    // ============================================

    /**
     * Receives properties JSON from the Properties Builder via AJAX POST.
     * Stores in $_SESSION['evo_wizard']['properties'].
     * Returns JSON with success status and next step URL.
     */
    public function store_properties(): void {
        $input = file_get_contents('php://input');
        $properties = json_decode($input, true);

        if (!is_array($properties)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid properties data received.'
            ]);
            return;
        }

        // Ensure wizard session exists
        if (!isset($_SESSION['evo_wizard'])) {
            $_SESSION['evo_wizard'] = [];
        }

        $_SESSION['evo_wizard']['properties'] = $properties;

        echo json_encode([
            'success' => true,
            'count' => count($properties),
            'next_step' => 'trongate_control-evo/choose_order_by'
        ]);
    }

    // ============================================
    // Wizard Step 5: Choose URL Column (Slug)
    // ============================================

    /**
     * Renders the URL column selection view.
     * Lists properties from the wizard session for the user to pick a slug column.
     */
    public function choose_url_column(): void {
        $data['view_module'] = 'trongate_control/evo';
        $data['properties'] = $_SESSION['evo_wizard']['properties'] ?? [];
        $this->view('choose_url_column', $data);
    }

    /**
     * Stores the selected URL column in the wizard session.
     */
    public function submit_url_column(): void {
        $url_column = post('url_column', true);
        $_SESSION['evo_wizard']['url_column'] = $url_column;

        $data['view_module'] = 'trongate_control/evo';
        $data['properties'] = $_SESSION['evo_wizard']['properties'] ?? [];
        $this->view('choose_order_by', $data);
    }

    // ============================================
    // Wizard Step 5b: Submit URL Column
    // ============================================

    /**
     * Accepts POST from mx-post on options selector.
     * Parameter name is 'selected'.
     */
    public function submit_url_col(): void {
        $selected = post('selected', true);
        $_SESSION['evo_wizard']['url_column'] = $selected;

        $data['view_module'] = 'trongate_control/evo';
        $data['properties'] = $_SESSION['evo_wizard']['properties'] ?? [];
        $this->view('choose_order_by', $data);
    }

    // ============================================
    // Wizard Step 6: Choose Default Order By
    // ============================================

    /**
     * Renders the default order by selection view.
     * Lists id, id DESC, and each property name + {name} DESC.
     */
    public function choose_order_by(): void {
        $data['view_module'] = 'trongate_control/evo';
        $data['properties'] = $_SESSION['evo_wizard']['properties'] ?? [];
        $this->view('choose_order_by', $data);
    }

    /**
     * Accepts POST from mx-post on options selector.
     * Parameter name is 'selected'. Stores in session and shows conf_generate_mod.
     */
    public function submit_order_by(): void {
        $selected = post('selected', true);
        $_SESSION['evo_wizard']['order_by'] = $selected;

        $data['view_module'] = 'trongate_control/evo';
        $data['wizard'] = $_SESSION['evo_wizard'];
        $this->view('conf_generate_mod', $data);
    }

    // ============================================
    // ============================================
    // Module Details (Overlay)
    // ============================================

    /**
     * Renders the module details page.
     * When called with '/web' segment, renders as a full-page template for iframe display.
     */
    public function conf_generate_mod(): void {
        $data['view_module'] = 'trongate_control/evo';
        $this->view('conf_generate_mod', $data);
    }

    // ============================================
    // Generate Module
    // ============================================

    /**
     * Generates the new module from wizard session data.
     * Delegates to Site_builder for the actual generation.
     */
    public function run_gen(): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];

        // Check that wizard data exists
        $module_name = $wizard['module_folder_name'] ?? '';
        if ($module_name === '') {
            $this->render_generation_error(
                'Module could not be created because no module name was provided.'
            );
            return;
        }

        // Map session data to Site_builder's expected format
        $data = [
            'module_folder_name' => $module_name,
            'record_name_singular' => $wizard['record_name_singular'] ?? 'Record',
            'record_name_plural' => $wizard['record_name_plural'] ?? 'Records',
            'nav_label' => $wizard['nav_label'] ?? '',
            'urlColumn' => $wizard['url_column'] ?? '',
            'orderBy' => $wizard['order_by'] ?? 'id',
            'properties' => json_encode($wizard['properties'] ?? []),
            'icon_id' => $wizard['icon_id'] ?? '',
            'icon_code' => $wizard['icon_code'] ?? '',
            'tempParams' => json_encode(['previousAction' => 'submitProperties']),
        ];

        // Load site_builder module and call generation
        $this->module('trongate_control-site_builder');
        $result = $this->site_builder->submit_generate_mod($data);

        if ($result['status'] === 'success') {
            $data['view_module'] = 'trongate_control/evo';
            $data['module_name'] = $module_name;
            $data['module_url'] = BASE_URL . $module_name . '/manage';
            $this->view('generate_module', $data);
        } else {
            $this->render_generation_error(
                $result['message'],
                $result['more_info_url'] ?? ''
            );
        }
    }

    /**
     * Render a generation error with optional "Learn More" link.
     */
    private function render_generation_error(string $message, string $more_info_url = ''): void {
        $data['view_module'] = 'trongate_control/evo';
        $data['message'] = $message;
        $data['more_info_url'] = $more_info_url;
        $this->view('generation_error', $data);
    }

    // ============================================
    // Wizard Step 8: Module Details (editable form)
    // ============================================

    public function module_details(): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];
        $properties = $wizard['properties'] ?? [];

        // Build URL column dropdown options
        $url_column_options = ['' => '-- No URL Column --'];
        foreach ($properties as $prop) {
            $name = $prop['propertyName'] ?? '';
            if ($name !== '') {
                $url_column_options[$name] = $name;
            }
        }

        // Build order by dropdown options
        $order_by_options = [
            'id' => 'id',
            'id DESC' => 'id DESC'
        ];
        foreach ($properties as $prop) {
            $name = $prop['propertyName'] ?? '';
            if ($name !== '') {
                $order_by_options[$name] = $name;
                $order_by_options[$name . ' DESC'] = $name . ' DESC';
            }
        }

        $data['view_module'] = 'trongate_control/evo';
        $data['wizard'] = $wizard;
        $data['url_column_options'] = $url_column_options;
        $data['order_by_options'] = $order_by_options;

        if (segment(3) === 'web') {
            // Full-page render for iframe overlay (via reload_iframe)
            $data['after_close_url'] = BASE_URL . 'trongate_control-evo/conf_generate_mod?template=c64';
            $data['form_location'] = str_replace('/conf_generate_mod', '/submit_mod_details', $data['after_close_url']);
            $data['after_close_width'] = 800;
            $data['after_close_height'] = 600;

            // Render the inner form content as a string
            $view_content = $this->view('conf_module_details', $data, true);

            // Seed localStorage from session data for the view's JS
            $data['view_content'] = $view_content;
            $data['local_storage_items'] = [
                'module_folder_name' => $wizard['module_folder_name'] ?? '',
                'record_name_singular' => $wizard['record_name_singular'] ?? '',
                'record_name_plural' => $wizard['record_name_plural'] ?? '',
                'nav_label' => $wizard['nav_label'] ?? '',
                'properties' => json_encode($wizard['properties'] ?? []),
                'urlColumn' => $wizard['url_column'] ?? '',
                'orderBy' => $wizard['order_by'] ?? 'id'
            ];

            $this->view('module_details_iframe', $data);
        } else {
            // In-page render (legacy)
            $this->view('conf_module_details', $data);
        }
    }

    /**
     * Accepts POST from the module details editable form.
     * Updates the wizard session and returns to conf_generate_mod.
     */
    public function submit_mod_details(): void {
        $this->validation->set_rules('moduleDir', 'module directory', 'required|callback_module_dir_check');
        $this->validation->set_rules('recordNameSingular', 'record name singular', 'required');
        $this->validation->set_rules('recordNamePlural', 'record name plural', 'required');
        $this->validation->set_rules('navLabel', 'navigation label', 'max_length[40]');
        $this->validation->set_rules('properties', 'properties', 'required|callback_properties_check');
        $this->validation->set_rules('urlColumn', 'URL column', 'callback_url_column_check');
        $this->validation->set_rules('orderBy', 'order by', 'required|callback_order_by_check');

        $result = $this->validation->run();

        if ($result === true) {
            $posted_items = post();
            unset($posted_items['csrf_token']);

            // Also save to session for backward compatibility
            $_SESSION['evo_wizard']['module_folder_name'] = $posted_items['moduleDir'] ?? '';
            $_SESSION['evo_wizard']['record_name_singular'] = $posted_items['recordNameSingular'] ?? '';
            $_SESSION['evo_wizard']['record_name_plural'] = $posted_items['recordNamePlural'] ?? '';
            $_SESSION['evo_wizard']['nav_label'] = $posted_items['navLabel'] ?? '';
            $properties_raw = $posted_items['properties'] ?? '';
            $properties = json_decode($properties_raw, true);
            if (is_array($properties)) {
                $_SESSION['evo_wizard']['properties'] = $properties;
            }
            $_SESSION['evo_wizard']['url_column'] = $posted_items['urlColumn'] ?? '';
            $_SESSION['evo_wizard']['order_by'] = $posted_items['orderBy'] ?? '';

            echo '<div class="posted-items-container cloak">';
            echo json_encode($posted_items);
            echo '</div>';
        } else {
            echo validation_errors(422);
        }
    }

    // ─── Validation Callbacks ─────────────────────────────────

    /**
     * Validates that the module directory name contains only lowercase letters, numbers, and underscores.
     */
    public function module_dir_check(string $moduleDir): bool|string {
        if ($moduleDir === '') {
            return true;
        }
        if (!preg_match('/^[a-z0-9_]+$/', $moduleDir)) {
            return 'Directory name can only contain lowercase letters, numbers, and underscores.';
        }
        return true;
    }

    /**
     * Validates the properties JSON string.
     */
    public function properties_check(string $properties): bool|string {
        if ($properties === '') {
            return true;
        }
        $decoded = json_decode($properties, true);
        if ($decoded === null) {
            return 'Properties must be valid JSON.';
        }
        if (!is_array($decoded)) {
            return 'Properties must be a JSON array.';
        }
        $propertyNames = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                return 'Each item in the properties array must be an object.';
            }
            if (!isset($item['propertyName']) || empty($item['propertyName'])) {
                return "Item at index {$index} is missing propertyName.";
            }
            if (!isset($item['propertyType']) || empty($item['propertyType'])) {
                return "Item at index {$index} is missing propertyType.";
            }
            $propertyNames[] = $item['propertyName'];
        }
        if (count($propertyNames) !== count(array_unique($propertyNames))) {
            return 'Property names must be unique.';
        }
        return true;
    }

    /**
     * Validates the URL column selection.
     */
    public function url_column_check(string $urlColumn): bool|string {
        $urlColumn = trim($urlColumn);
        if (empty($urlColumn)) {
            return true;
        }
        if (strtolower($urlColumn) === 'id') {
            return true;
        }
        $properties = post('properties', true);
        $decoded = json_decode($properties, true);
        if (!is_array($decoded)) {
            return 'URL column could not be validated against properties.';
        }
        foreach ($decoded as $item) {
            if (isset($item['propertyName']) && $item['propertyName'] === $urlColumn) {
                return true;
            }
        }
        return "URL column '{$urlColumn}' does not match any property name.";
    }

    /**
     * Validates the order by field selection.
     */
    public function order_by_check(string $orderBy): bool|string {
        $orderBy = trim($orderBy);
        if (empty($orderBy)) {
            return 'Order by field is required.';
        }
        $properties = post('properties', true);
        $decoded = json_decode($properties, true);
        if (!is_array($decoded)) {
            return 'Order by could not be validated against properties.';
        }
        $propertyNames = [];
        foreach ($decoded as $item) {
            if (isset($item['propertyName'])) {
                $propertyNames[] = $item['propertyName'];
            }
        }
        $propertyNames[] = 'id';
        foreach ($propertyNames as $propertyName) {
            if ($orderBy === $propertyName || $orderBy === $propertyName . ' DESC') {
                return true;
            }
        }
        return "Order by value '{$orderBy}' does not match any valid property name.";
    }

    // ============================================
    // Private Helpers
    // ============================================

    /**
     * Render an error message view.
     */
    private function render_error(string $message): string {
        $data['view_module'] = 'trongate_control/evo';
        $data['message'] = $message;
        return $this->view('error_element', $data, true);
    }

    /**
     * Render a disabled response when the module is accessed outside dev mode.
     */
    public function render_disabled_response(): void {
        http_response_code(403);
        $data['view_module'] = 'trongate_control/evo';
        $this->view('disabled', $data);
    }

}