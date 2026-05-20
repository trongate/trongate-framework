&lt;?php
/**
 * <?= $controller_name ?> Controller
 *
 * Manages <?= strtolower($record_name_singular) ?> records with full CRUD operations.
 */
class <?= $controller_name ?> extends Trongate {

    private int $default_limit = 20;
    private array $per_page_options = [10, 20, 50, 100];
    <?php // $has_searchable is passed from the controller ?>

    /**
     * Default entry point - redirects to manage page
     *
     * @return void
     */
    public function index(): void {
        redirect('<?= strtolower($controller_name) ?>/manage');
    }

    /**
     * Display paginated list of <?= strtolower($record_name_plural) ?>.
     *
     * Shows records in a table with pagination controls. Includes
     * dropdown for selecting number of records per page.
     *
     * @return void
     */
    public function manage(): void {
        $this->trongate_security->make_sure_allowed();

        <?php if ($has_searchable): ?>$search_query = $_GET['search_query'] ?? '';
        $search_column = $_GET['search_column'] ?? '';

        // Validate column against known searchable columns.
        $allowed_columns = $this->model->get_searchable_columns();
        if ($search_column !== '' && !in_array($search_column, $allowed_columns, true)) {
            $search_column = '';
        }

        $search_active = ($search_query !== '');

        if ($search_active) {
            $total_rows = $this->model->count_search_results($search_query, $search_column);
        } else {
            $total_rows = $this->model->count_all();
        }
        <?php else: ?>$total_rows = $this->model->count_all(); // Required for pagination.
        <?php endif; ?>

        $limit = $this->get_limit();
        $offset = $this->get_offset();

        <?php if ($has_searchable): ?>if ($search_active) {
            $rows = $this->model->search_records($search_query, $search_column, $limit, $offset);
        } else {
            $rows = $this->model->fetch_records($limit, $offset);
        }
        <?php else: ?>$rows = $this->model->fetch_records($limit, $offset);
        <?php endif; ?>$rows = $this->model->prepare_records_for_display($rows);

        $data = [
            'rows' => $rows,
            'pagination_data' => $this->get_pagination_data($total_rows, $limit<?php if ($has_searchable): ?>, $search_query, $search_column<?php endif; ?>),
            'view_module' => '<?= strtolower($controller_name) ?>',
            'view_file' => 'manage',
            'per_page_options' => $this->per_page_options,
            'selected_per_page' => $this->get_selected_per_page()
        ];

        <?php if ($has_searchable): ?>$data['search_query'] = $search_query;
        $data['search_column'] = $search_column;
        $data['search_active'] = $search_active;
        <?php endif; ?>$this->templates->admin($data);
    }

    /**
     * Display form for creating or editing a <?= strtolower($record_name_singular) ?>.
     *
     * Shows form with appropriate headline and action URL.
     * Automatically repopulates form with submitted data on validation errors.
     *
     * @return void
     */
    public function create(): void {
        $this->trongate_security->make_sure_allowed();

        $update_id = segment(3, 'int');

        if ((REQUEST_TYPE === 'GET') && ($update_id > 0)) {
            $data = $this->model->get_data_from_db($update_id);
        } else {
            $data = $this->model->get_data_from_post();
        }

        // Add view-specific data
        $data['headline'] = ($update_id > 0) ? 'Update <?= ucwords($record_name_singular) ?> Record' : 'Create New <?= ucwords($record_name_singular) ?> Record';
        $data['cancel_url'] = ($update_id > 0) ? '<?= strtolower($controller_name) ?>/show/'.$update_id : '<?= strtolower($controller_name) ?>/manage';
        $data['form_location'] = '<?= strtolower($controller_name) ?>/submit/'.$update_id;
        $data['view_module'] = '<?= strtolower($controller_name) ?>';
        $data['view_file'] = 'create';
        $this->templates->admin($data);
    }

    /**
     * Handle form submission for creating/updating <?= strtolower($record_name_plural) ?>.
     *
     * Validates input, converts checkbox data, and saves to database.
     * Includes automatic CSRF validation and proper checkbox conversion.
     *
     * @return void
     */
    public function submit(): void {
        $this->trongate_security->make_sure_allowed();

        $submit = post('submit', true);

        if ($submit === 'Submit') {
<?= $dynamic_validation_tests ?>

            if ($this->validation->run()) {
                $update_id = segment(3, 'int');
                $data = $this->model->get_data_from_post();

                if ($update_id > 0) {
                    $this->model->update_record($update_id, $data);
                    $flash_msg = '<?= ucfirst($record_name_singular) ?> updated successfully';
                    $finish_url = '<?= strtolower($controller_name) ?>/show/'.$update_id;
                } else {
                    $update_id = $this->model->create_new_record($data);
                    $flash_msg = '<?= ucfirst($record_name_singular) ?> created successfully';
                    $finish_url = '<?= strtolower($controller_name) ?>/manage';
                }

                set_flashdata($flash_msg);
                redirect($finish_url);
            } else {
                $this->create();
            }
        } else {
            redirect('<?= strtolower($controller_name) ?>/manage');
        }
    }

    /**
     * Display detailed view of a single <?= strtolower($record_name_singular) ?>.
     *
     * Shows all details with edit/delete options.
     * Automatically handles missing records with 404 page.
     *
     * @return void
     */
    public function show(): void {
        $this->trongate_security->make_sure_allowed();

        $update_id = segment(3, 'int');

        if ($update_id === 0) {
            redirect('<?= $module_folder_name ?>/manage');
        }

        // Fetch record and prepare for display.
        $data = $this->model->get_data_from_db($update_id, true);

        if ($data === false) {
            $this->not_found();
            return;
        }

        // Add additional view data
        $data['update_id'] = $update_id;
        $data['headline'] = '<?= ucwords($record_name_singular) ?> Details';
        $data['back_url'] = $this->get_back_url();
        $data['view_module'] = '<?= strtolower($controller_name) ?>';
        $data['view_file'] = 'show';
        $this->templates->admin($data);
    }

    /**
     * Display confirmation page before deleting a <?= strtolower($record_name_singular) ?>.
     *
     * Shows confirmation dialog with details to prevent accidental deletion.
     *
     * @return void
     */
    public function delete_conf(): void {
        $this->trongate_security->make_sure_allowed();

        $update_id = segment(3, 'int');

        if ($update_id === 0) {
            $this->not_found();
            return;
        }

        $data = $this->model->get_data_for_edit($update_id);

        if ($data === false) {
            $this->not_found();
            return;
        }

        $data['update_id'] = $update_id;
        $data['headline'] = 'Delete <?= ucwords($record_name_singular) ?> Record';
        $data['cancel_url'] = '<?= strtolower($controller_name) ?>/show/'.$update_id;
        $data['form_location'] = '<?= strtolower($controller_name) ?>/submit_delete/'.$update_id;
        $data['view_module'] = '<?= strtolower($controller_name) ?>';
        $data['view_file'] = 'delete_conf';
        $this->templates->admin($data);
    }

    /**
     * Handle <?= strtolower($record_name_singular) ?> deletion after confirmation.
     *
     * Verifies confirmation and deletes record from database.
     * Includes safety checks to prevent unauthorized deletion.
     *
     * @return void
     */
    public function submit_delete(): void {
        $this->trongate_security->make_sure_allowed();

        $submit = post('submit', true);

        if ($submit === 'Yes - Delete Now') {
            $update_id = segment(3, 'int');

            if ($update_id === 0) {
                redirect('<?= strtolower($controller_name) ?>/manage');
                return;
            }

            $record = $this->model->find_by_id($update_id);

            if ($record === false) {
                redirect('<?= strtolower($controller_name) ?>/manage');
                return;
            }

            $this->model->delete_record($update_id);

            set_flashdata('The record was successfully deleted');
            redirect('<?= strtolower($controller_name) ?>/manage');
        } else {
            redirect('<?= strtolower($controller_name) ?>/manage');
        }
    }

    /**
     * Set number of records per page for pagination.
     *
     * Stores user preference in session for consistent pagination across requests.
     *
     * @return void
     */
    public function set_per_page(): void {
        $this->trongate_security->make_sure_allowed();

        $selected_index = segment(3, 'int');

        if (!isset($this->per_page_options[$selected_index])) {
            $selected_index = 1;
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('<?= strtolower($controller_name) ?>/manage');
    }

    /**
     * Generate pagination configuration data.
     *
     * @param int    $total_rows    Total number of records
     * @param int    $limit         Number of records per page
<?php if ($has_searchable): ?>
     * @param string $search_query  The search query string
     * @param string $search_column The column being searched
<?php endif; ?>
     * @return array Pagination configuration for template
     */
    private function get_pagination_data(int $total_rows, int $limit<?php if ($has_searchable): ?>, string $search_query = '', string $search_column = ''<?php endif; ?>): array {
        <?php if ($has_searchable): ?>$pagination_query = '';
        if ($search_query !== '' && $search_column !== '') {
            $pagination_query = 'search_query=' . urlencode($search_query) . '&search_column=' . urlencode($search_column);
        }

        <?php endif; ?>return [
            'total_rows' => $total_rows,
            'limit' => $limit,
            'pagination_root' => '<?= strtolower($controller_name) ?>/manage',
<?php if ($has_searchable): ?>
            'pagination_query' => $pagination_query,
<?php endif; ?>
            'record_name_plural' => '<?= strtolower($record_name_plural) ?>',
            'include_showing_statement' => true
        ];
    }

    /**
     * Determine appropriate back URL for navigation.
     *
     * Uses previous URL if it was the manage page, otherwise defaults to manage.
     *
     * @return string URL for back button
     */
    private function get_back_url(): string {
        $previous_url = previous_url();
        if ($previous_url !== '' && strpos($previous_url, BASE_URL . '<?= strtolower($controller_name) ?>/manage') === 0) {
            return $previous_url;
        }
        return BASE_URL . '<?= strtolower($controller_name) ?>/manage';
    }

    /**
     * Display 404-style not found page for missing <?= strtolower($record_name_plural) ?>.
     *
     * Shows user-friendly error message with navigation back.
     *
     * @return void
     */
    private function not_found(): void {
        $data = [
            'headline' => '<?= ucwords($record_name_singular) ?> Not Found',
            'message' => 'The <?= strtolower($record_name_singular) ?> you\'re looking for doesn\'t exist or has been deleted.',
            'back_url' => $this->get_back_url(),
            'back_label' => 'Go Back',
            'view_module' => '<?= strtolower($controller_name) ?>',
            'view_file' => 'not_found'
        ];
        $this->templates->admin($data);
    }

    /**
     * Get selected per-page index from session.
     *
     * @return int Index of selected per-page option
     */
    private function get_selected_per_page(): int {
        return $_SESSION['selected_per_page'] ?? 1;
    }

    /**
     * Get current pagination limit from session.
     *
     * @return int Number of records to display per page
     */
    private function get_limit(): int {
        if (isset($_SESSION['selected_per_page'])) {
            return $this->per_page_options[$_SESSION['selected_per_page']];
        }
        return $this->default_limit;
    }

    /**
     * Calculate pagination offset based on page number.
     *
     * @return int Database offset for current page
     */
    private function get_offset(): int {
        $page_num = segment(3, 'int');
        return ($page_num > 1) ? ($page_num - 1) * $this->get_limit() : 0;
    }

<?php if ($has_searchable): ?>
    /**
     * Handle search form submission for filtering <?= strtolower($record_name_plural) ?>.
     *
     * Preprocesses text parameters and triggers custom query filtering.
     *
     * @return void
     */
    public function submit_search(): void {
        $this->trongate_security->make_sure_allowed();

        $search_query = post('search_query', true);
        $search_column = post('search_column', true);

        // Validate column against known searchable columns.
        $allowed_columns = $this->model->get_searchable_columns();
        if ($search_column !== '' && !in_array($search_column, $allowed_columns, true)) {
            $search_column = '';
        }

        // Preprocess the query
        $search_query = trim($search_query);
        $search_query = preg_replace('/\s+/', ' ', $search_query);

        // Validate minimum 2 character length
        if (strlen($search_query) < 2) {
            set_flashdata('Search query must be at least 2 characters');
            redirect('<?= strtolower($controller_name) ?>/manage');
        }

        redirect('<?= strtolower($controller_name) ?>/manage?search_query=' . urlencode($search_query) . '&search_column=' . urlencode($search_column));
    }

    /**
     * Display the search modal form for filtering <?= strtolower($record_name_plural) ?>.
     *
     * Renders a form with a search input and a dropdown of searchable columns.
     *
     * @return void
     */
    public function search_modal(): void {
        $this->trongate_security->make_sure_allowed();

        $data['view_module'] = '<?= strtolower($controller_name) ?>';
        $data['view_file'] = 'search_modal';
        $this->view('search_modal', $data);
    }
<?php endif; ?>

<?= $dynamic_callback_methods ?>

}
