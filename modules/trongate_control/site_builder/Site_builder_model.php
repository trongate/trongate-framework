<?php
require_once('controller_helper.php');
require_once('model_helper.php');
class Site_builder_model extends Model {

    public function check_write_permissions(array $additional_paths = []): bool {

        $paths = array_merge([
            APPPATH . 'config',
            APPPATH . 'modules'
        ], $additional_paths);

        foreach ($paths as $path) {
            if (!is_writable($path)) {
                return false;
            }
        }

        return true;
    }

    public function empty_directory(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full_path = $path . '/' . $item;
            if (is_dir($full_path)) {
                $this->empty_directory($full_path);
                rmdir($full_path);
            } else {
                unlink($full_path);
            }
        }
    }

    /**
     * Check whether any property in the given JSON has isSearchable set to 'yes'.
     *
     * @param string $properties_json Raw JSON string of properties.
     * @return bool True if at least one property is searchable.
     */
    public function has_searchable_property(string $properties_json): bool {
        $properties = json_decode($properties_json);
        $posted = $this->est_posted_properties($properties);
        foreach ($posted as $prop) {
            if (($prop->is_searchable ?? 'no') === 'yes') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the form_field_name values for all searchable properties.
     *
     * @param string $properties_json Raw JSON string of properties.
     * @return array<string> Array of form_field_name strings.
     */
    public function get_searchable_columns(string $properties_json): array {
        $columns = [];
        $properties = json_decode($properties_json);
        $posted = $this->est_posted_properties($properties);
        foreach ($posted as $prop) {
            if (($prop->is_searchable ?? 'no') === 'yes') {
                $columns[] = $prop->form_field_name;
            }
        }
        return $columns;
    }

    /**
     * Render a view file and either return the output as a string or display it.
     *
     * @param string    $view          View file name (without .php extension).
     * @param array     $data          Associative array of data to extract into the view.
     * @param bool|null $return_as_str Whether to return the rendered view as a string.
     *                                 Defaults to true. Pass false to output directly.
     * @return string|null The rendered output if $return_as_str is true; otherwise null.
     */
    private function render(string $view, array $data = [], ?bool $return_as_str = null): ?string {
        $return_as_str = $return_as_str ?? true;
        $view_path = APPPATH . 'modules/trongate_control/site_builder/views/' . $view . '.php';
        extract($data);

        if ($return_as_str) {
            ob_start();
            require($view_path);
            return ob_get_clean();
        } else {
            require($view_path);
            return null;
        }
    }

    public function est_posted_properties($properties) {
        $posted_properties = [];

        foreach($properties as $property) {
            $property_type = trim($property->propertyType ?? '');

            if ($property_type === 'date range') {
                $split = $this->split_date_range_property($property);
                $posted_properties[] = $split['start'];
                $posted_properties[] = $split['end'];
            } elseif ($property_type === 'time range') {
                $split = $this->split_time_range_property($property);
                $posted_properties[] = $split['start'];
                $posted_properties[] = $split['end'];
            } else {
                $row_data['property_name'] = $property->propertyName ?? '';
                $row_data['property_type'] = $property_type;
                $row_data['on_form'] = $property->onForm ?? 'yes';
                $row_data['is_searchable'] = $property->isSearchable ?? 'no';
                $row_data['on_summary_table'] = $property->onSummaryTable ?? 'no';
                $row_data['validation_rules'] = $property->validationRules ?? [];
                $row_data['form_field_name'] = build_form_field_name((object) $row_data);
                $posted_properties[] = (object) $row_data;
            }
        }

        return $posted_properties;
    }

    private function split_date_range_property($property): array {
        $base_name = $property->propertyName ?? 'Date Range';
        $base_field = str_replace('-', '_', url_title($base_name));
        $on_form = $property->onForm ?? 'yes';
        $is_searchable = $property->isSearchable ?? 'no';
        $on_summary_table = $property->onSummaryTable ?? 'no';
        $all_rules = $property->validationRules ?? [];

        // Derive sub-labels from the property name
        $labels = $this->derive_range_labels($base_name);

        // Extract start-specific rules (strip "start date " prefix)
        $start_rules = [];
        foreach ($all_rules as $rule) {
            if (str_starts_with($rule, 'start date ')) {
                $start_rules[] = substr($rule, 11); // strip "start date "
            }
        }

        // Extract end-specific rules (strip "end date " prefix)
        $end_rules = [];
        foreach ($all_rules as $rule) {
            if (str_starts_with($rule, 'end date ')) {
                $end_rules[] = substr($rule, 9); // strip "end date "
            }
        }

        $start = (object) [
            'property_name' => $labels['start'],
            'property_type' => 'date',
            'on_form' => $on_form,
            'is_searchable' => $is_searchable,
            'on_summary_table' => $on_summary_table,
            'validation_rules' => $start_rules,
            'form_field_name' => $base_field . '_start'
        ];

        $end = (object) [
            'property_name' => $labels['end'],
            'property_type' => 'date',
            'on_form' => $on_form,
            'is_searchable' => $is_searchable,
            'on_summary_table' => $on_summary_table,
            'validation_rules' => $end_rules,
            'form_field_name' => $base_field . '_end'
        ];

        return ['start' => $start, 'end' => $end];
    }

    private function split_time_range_property($property): array {
        $base_name = $property->propertyName ?? 'Time Range';
        $base_field = str_replace('-', '_', url_title($base_name));
        $on_form = $property->onForm ?? 'yes';
        $is_searchable = $property->isSearchable ?? 'no';
        $on_summary_table = $property->onSummaryTable ?? 'no';
        $all_rules = $property->validationRules ?? [];

        $labels = $this->derive_range_labels($base_name);

        // Extract start-specific rules (strip "start time " prefix)
        $start_rules = [];
        foreach ($all_rules as $rule) {
            if (str_starts_with($rule, 'start time ')) {
                $start_rules[] = substr($rule, 11); // strip "start time "
            }
        }

        // Extract end-specific rules (strip "end time " prefix)
        $end_rules = [];
        foreach ($all_rules as $rule) {
            if (str_starts_with($rule, 'end time ')) {
                $end_rules[] = substr($rule, 9); // strip "end time "
            }
        }

        $start = (object) [
            'property_name' => $labels['start'],
            'property_type' => 'time',
            'on_form' => $on_form,
            'is_searchable' => $is_searchable,
            'on_summary_table' => $on_summary_table,
            'validation_rules' => $start_rules,
            'form_field_name' => $base_field . '_start'
        ];

        $end = (object) [
            'property_name' => $labels['end'],
            'property_type' => 'time',
            'on_form' => $on_form,
            'is_searchable' => $is_searchable,
            'on_summary_table' => $on_summary_table,
            'validation_rules' => $end_rules,
            'form_field_name' => $base_field . '_end'
        ];

        return ['start' => $start, 'end' => $end];
    }

    private function derive_range_labels(string $name): array {
        // Try to split on " And " for natural sub-labels
        if (preg_match('/^(.+)\s+and\s+(.+)$/i', $name, $matches)) {
            return [
                'start' => trim($matches[1]),
                'end' => trim($matches[2])
            ];
        }

        // Fallback: use suffixes
        return [
            'start' => $name . ' Start',
            'end' => $name . ' End'
        ];
    }

    public function build_validation_tests($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $data['validation_tests'] = build_dynamic_validation_tests($data);
        $output = $this->render('dynamic_validation_tests', $data, true);
        return $output;
    }

    public function return_table_properties($posted_properties) {
        $properties = [];

        foreach($posted_properties as $property) {
            if ($property->on_summary_table === 'yes') {
                $properties[] = $property;
            }
        }
        
        return $properties;
    }

    public function build_dynamic_form_fields($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $data['form_field_rows'] = build_dynamic_form_fields($data);
        $output = $this->render('dynamic_form_fields', $data, true);
        return $output;
    }

    public function build_dynamic_posted_data($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $output = $this->render('dynamic_posted_data', $data, true);
        return $output;     
    }

    public function build_dynamic_prepared_record($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $output = $this->render('dynamic_prepared_record', $data, true);
        return $output;       
    }

    public function build_dynamic_date_constraint_js($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $data['needs_date_constraint_js'] = needs_date_constraint_js($data);
        $output = $this->render('dynamic_date_constraint_js', $data, true);
        return $output;
    }

    public function build_dynamic_callback_methods($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $data['callback_methods'] = build_dynamic_callback_methods($data);
        $output = $this->render('dynamic_callback_methods', $data, true);
        return $output;
    }

    public function build_dynamic_validation_methods($data) {
        $properties = json_decode($data['properties']);
        $data['posted_properties'] = $this->est_posted_properties($properties);
        $data['validation_methods'] = build_dynamic_validation_methods($data);
        $output = $this->render('dynamic_validation_methods', $data, true);
        return $output;
    }

    public function prep_file_contents(string $content): string {
        return str_replace(
            ['&lt;', '&gt;'],
            ['<', '>'],
            $content
        );
    }

    /**
     * Add a navigation menu link to the admin template's side-nav-menu
     * (and mobile slide-nav-list) for a newly generated module.
     *
     * Rules:
     *  1. nav_label must not be empty.
     *  2. The link must not already exist in the menu.
     *
     * @param string $module_name The module folder name (used for the href).
     * @param string $nav_label   The display label for the navigation link.
     * @return bool True if a link was added, false otherwise.
     */
    public function add_nav_menu_link(string $module_name, string $nav_label): bool {
        if ($nav_label === '') {
            return false;
        }

        $template_path = APPPATH . 'modules/templates/views/admin.php';

        if (!is_writable($template_path)) {
            return false;
        }

        $content = file_get_contents($template_path);

        // Check if a link to this module already exists anywhere in the template.
        $existing_hrefs = [
            'href="' . $module_name . '/manage"',
            'href="' . $module_name . '"',
        ];
        foreach ($existing_hrefs as $href) {
            if (strpos($content, $href) !== false) {
                return false; // Link already present; nothing to do.
            }
        }

        // ---- Desktop side-nav-menu ----
        $desktop_link = '            <li>' . "\n"
                      . '                <a href="' . $module_name . '/manage">' . "\n"
                      . '                    ' . htmlspecialchars($nav_label, ENT_NOQUOTES, 'UTF-8') . "\n"
                      . '                </a>' . "\n"
                      . '            </li>';

        // Match the closing </ul> of side-nav-menu (8-space indent, followed by </nav> at 4-space indent).
        $side_nav_pattern = '/(        <\/ul>\n    <\/nav>)/';
        $content = preg_replace($side_nav_pattern, $desktop_link . "\n" . '$1', $content, 1);

        // ---- Mobile slide-nav-list ----
        $mobile_link = '    <li><a href="' . $module_name . '/manage">'
                     . htmlspecialchars($nav_label, ENT_NOQUOTES, 'UTF-8')
                     . '</a></li>';

        // Match the closing </ul> of slide-nav-list (2-space indent, followed by </nav>).
        $slide_nav_pattern = '/(  <\/ul>\n<\/nav>)/';
        $content = preg_replace($slide_nav_pattern, $mobile_link . "\n" . '$1', $content, 1);

        file_put_contents($template_path, $content);

        return true;
    }

}
