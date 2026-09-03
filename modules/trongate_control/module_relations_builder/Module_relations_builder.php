<?php
/**
 * Module_relations_builder - Flo's wizard for generating module relations.
 *
 * Walks the user through relation setup (choose type → pick parent/child
 * modules → bridging question for one-to-one → editable details), then, on
 * run_gen(), performs the generation in one atomic sequence: schema SQL,
 * settings JSON, then code injection into the two generated modules.
 *
 * This is generation-time only. The runtime counterpart lives in the
 * top-level module_relations module; this controller writes the schema and
 * the settings JSON (the runtime contract) that module_relations reads.
 *
 * The wizard is a developer tool: it only runs when ENV is 'dev'.
 */
class Module_relations_builder extends Trongate {

    /**
     * Valid relation type keys (mirrors the options-selector list).
     */
    private const RELATION_TYPES = ['one to one', 'one to many', 'many to many'];

    /**
     * Sentinel anchor: insert before the class-closing brace (end of file).
     */
    private const END_OF_CLASS = '@end_of_class';

    /**
     * Sentinel anchor: append after the view's final closing </div> (the
     * details card). The summary panel belongs BELOW the card — matching
     * the academy_awards reference layout — not above it.
     */
    private const END_OF_VIEW = '@end_of_view';

    /**
     * Constructor — dev-mode guard, identical pattern to sibling child modules.
     *
     * Loads the generic Flo (evo) module for shared utilities
     * (render_error(), render_generation_error(), render_details_iframe()).
     *
     * @param string|null $module_name The module name (set by the framework).
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        $this->module('trongate_control-evo');

        if (strtolower(ENV) !== 'dev') {
            $this->evo->render_disabled_response();
            die();
        }
    }

    /**
     * Wizard entry point — renders the 'choose relation type' step.
     *
     * Clears any stale wizard session state so every run starts fresh.
     *
     * @return void
     */
    public function choose_relation_type(): void {
        unset($_SESSION['evo_wizard']);
        $data['view_module'] = 'trongate_control/module_relations_builder';
        $this->view('choose_relation_type', $data);
    }

    /**
     * Accepts the relation type selection and advances to module selection.
     *
     * @return void
     */
    public function submit_relation_type(): void {
        $selected = post('selected', true);

        if (!in_array($selected, self::RELATION_TYPES, true)) {
            echo $this->evo->render_error('Please choose a valid relation type.');
            return;
        }

        $_SESSION['evo_wizard']['relation_type'] = $selected;

        $this->render_module_selection(1);
    }

    /**
     * Renders the parent (stage 1) or child (stage 2) module picker.
     *
     * For 'one to many' the steps are labelled Parent/Child; for the other
     * types, Module A/Module B. The child list excludes the parent module.
     *
     * @param int $stage 1 = parent (module a), 2 = child (module b).
     * @return void
     */
    private function render_module_selection(int $stage): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];
        $type = $wizard['relation_type'] ?? '';
        if ($type === '') {
            $this->choose_relation_type();
            return;
        }

        $one_to_many = ($type === 'one to many');
        $parent = $wizard['parent_module'] ?? '';

        if ($stage === 1) {
            $heading = $one_to_many ? 'Select Parent Module' : 'Select Module A';
            $submit_method = 'submit_parent';
            $modules = $this->model->get_relation_tables();
        } else {
            $heading = $one_to_many ? 'Select Child Module' : 'Select Module B';
            $submit_method = 'submit_child';
            $modules = array_values(array_filter(
                $this->model->get_relation_tables(),
                fn($m) => $m !== $parent
            ));
        }

        if (count($modules) === 0) {
            echo $this->evo->render_error('No modules with database tables were found. Generate a module first.');
            return;
        }

        $data['view_module'] = 'trongate_control/module_relations_builder';
        $data['heading'] = $heading;
        $data['submit_method'] = $submit_method;
        $data['modules'] = $modules;
        $this->view('select_module', $data);
    }

    /**
     * Accepts the parent (module a) selection and advances the wizard.
     *
     * NOTE: method name must NOT contain MODULE_ASSETS_TRIGGER (`_module`)
     * as a substring — the router would treat it as an asset request
     * (engine/Core.php serve_module_asset) and 403. Same rule as module
     * names.
     *
     * @return void
     */
    public function submit_parent(): void {
        $selected = post('selected', true);

        if ($selected === '' || !$this->model->is_relation_table($selected)) {
            echo $this->evo->render_error('Please choose a valid module.');
            return;
        }

        $_SESSION['evo_wizard']['parent_module'] = $selected;

        $this->render_module_selection(2);
    }

    /**
     * Accepts the child (module b) selection, derives defaults, and either
     * asks the bridging question (one to one) or shows the confirmation.
     *
     * Singular/plural names and identifier columns are derived once here
     * and stored in the wizard session. Pluralization uses the real rules
     * (e.g. "person" → "people", not "persons"). Flow branches on type:
     * one to many and many to many go straight to confirmation (bridging is
     * implied); one to one asks the bridging question first.
     *
     * NOTE: method name must NOT contain MODULE_ASSETS_TRIGGER (`_module`)
     * as a substring — see submit_parent() note.
     *
     * @return void
     */
    public function submit_child(): void {
        $selected = post('selected', true);
        $parent = $_SESSION['evo_wizard']['parent_module'] ?? '';

        if ($selected === '' || !$this->model->is_relation_table($selected) || $selected === $parent) {
            echo $this->evo->render_error('Please choose a valid module.');
            return;
        }

        $_SESSION['evo_wizard']['child_module'] = $selected;

        $this->module('trongate_control-plural_maker');
        $singular_a = $this->plural_maker->get_singular($parent);
        $singular_b = $this->plural_maker->get_singular($selected);
        $_SESSION['evo_wizard']['singular_a'] = $singular_a;
        $_SESSION['evo_wizard']['singular_b'] = $singular_b;
        
        // Derive proper plural forms (e.g. "person" -> "people", not "persons").
        $_SESSION['evo_wizard']['plural_a'] = $this->plural_maker->make_plural($singular_a);
        $_SESSION['evo_wizard']['plural_b'] = $this->plural_maker->make_plural($singular_b);
        $_SESSION['evo_wizard']['identifier_column_a'] = $this->model->get_default_identifier_column($parent);
        $_SESSION['evo_wizard']['identifier_column_b'] = $this->model->get_default_identifier_column($selected);

        $type = $_SESSION['evo_wizard']['relation_type'] ?? '';

        if ($type === 'one to many') {
            $_SESSION['evo_wizard']['bridging_table'] = false;
            $this->render_confirmation();
            return;
        }

        if ($type === 'many to many') {
            $_SESSION['evo_wizard']['bridging_table'] = true;
            $this->render_confirmation();
            return;
        }

        // one to one — ask the bridging question (v1 UX)
        $data['view_module'] = 'trongate_control/module_relations_builder';
        $this->view('bridging_question', $data);
    }

    /**
     * Accepts the bridging-table answer for one-to-one relations.
     *
     * @return void
     */
    public function submit_bridging_option(): void {
        $selected = post('selected', true);

        if (!in_array($selected, ['Yes', 'No'], true)) {
            echo $this->evo->render_error('Please choose a valid option.');
            return;
        }

        $_SESSION['evo_wizard']['bridging_table'] = ($selected === 'Yes');
        $this->render_confirmation();
    }

    /**
     * Renders the pre-generation confirmation page from wizard data.
     *
     * Bounces back to the type picker when either module is missing from
     * the session (stale or partial wizard state).
     *
     * @return void
     */
    private function render_confirmation(): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];
        if (($wizard['parent_module'] ?? '') === '' || ($wizard['child_module'] ?? '') === '') {
            $this->choose_relation_type();
            return;
        }
        $data['view_module'] = 'trongate_control/module_relations_builder';
        $this->view('conf_generate_relation', $data);
    }

    /**
     * Renders the editable relation details overlay.
     *
     * Two render paths, fed from the same $data: '/web' renders as a full
     * page inside evo's shared module_details_iframe (the 'View Relation
     * Details' review step — DRY, no hand-rolled shell); otherwise it
     * renders in-page on the confirmation screen. Both paths seed
     * localStorage from the wizard session for the view's JS, so neither
     * ever renders with undefined variables or a dead form.
     *
     * @return void
     */
    public function relation_details(): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];

        $data['view_module'] = 'trongate_control/module_relations_builder';
        $data['wizard'] = $wizard;
        $data['relation_type_options'] = [
            'one to one' => 'One to One',
            'one to many' => 'One to Many',
            'many to many' => 'Many to Many'
        ];
        $data['page_title'] = 'Relation Details';
        $data['after_close_url'] = BASE_URL . 'trongate_control-module_relations_builder/conf_generate_relation?template=c64';
        $data['form_location'] = str_replace('/conf_generate_relation', '/submit_relation_details', $data['after_close_url']);
        $data['after_close_width'] = 800;
        $data['after_close_height'] = 600;

        if (segment(3) === 'web') {
            // Full-page render for the iframe overlay (via reload_iframe).
            $view_content = $this->view('conf_relation_details', $data, true);

            // Seed localStorage from session data for the view's JS.
            $data['view_content'] = $view_content;
            $data['local_storage_items'] = [
                'relation_type' => $wizard['relation_type'] ?? '',
                'parent_module' => $wizard['parent_module'] ?? '',
                'child_module' => $wizard['child_module'] ?? '',
                'identifier_column_a' => $wizard['identifier_column_a'] ?? '',
                'identifier_column_b' => $wizard['identifier_column_b'] ?? '',
                'bridging_table' => ($wizard['bridging_table'] ?? false) ? 'Yes' : 'No'
            ];

            $this->evo->render_details_iframe($data);
        } else {
            // In-page render (legacy) — same data, working form.
            $this->view('conf_relation_details', $data);
        }
    }

    /**
     * Accepts POST from the relation details editable form.
     *
     * Validates the editable fields (identifier columns must exist on their
     * tables — see the callbacks below) and writes the edited values back
     * into the wizard session on success. The posted items are echoed as
     * JSON inside a cloaked div for the parent page's JS to consume.
     *
     * @return void
     */
    public function submit_relation_details(): void {
        $this->validation->set_rules('identifierColumnA', 'identifier column A', 'required|callback_identifier_column_a_check');
        $this->validation->set_rules('identifierColumnB', 'identifier column B', 'required|callback_identifier_column_b_check');

        $result = $this->validation->run();

        if ($result === true) {
            $posted_items = post();
            unset($posted_items['csrf_token']);

            $_SESSION['evo_wizard']['identifier_column_a'] = trim($posted_items['identifierColumnA'] ?? '');
            $_SESSION['evo_wizard']['identifier_column_b'] = trim($posted_items['identifierColumnB'] ?? '');

            echo '<div class="posted-items-container cloak">';
            echo json_encode($posted_items);
            echo '</div>';
        } else {
            echo validation_errors(422);
        }
    }

    /**
     * Validation callback for identifier column A.
     *
     * Comma-separated multi-column identifiers are allowed; every part must
     * be a real column on the parent table.
     *
     * @param string $identifierColumnA The posted identifier column value.
     * @return bool|string True when valid, error message string otherwise.
     */
    public function identifier_column_a_check(string $identifierColumnA): bool|string {
        $parent = $_SESSION['evo_wizard']['parent_module'] ?? '';
        if (!$this->model->identifier_columns_valid($identifierColumnA, $parent)) {
            return 'Identifier column A contains a column that does not exist on the ' . $parent . ' table.';
        }
        return true;
    }

    /**
     * Validation callback for identifier column B.
     *
     * Comma-separated multi-column identifiers are allowed; every part must
     * be a real column on the child table.
     *
     * @param string $identifierColumnB The posted identifier column value.
     * @return bool|string True when valid, error message string otherwise.
     */
    public function identifier_column_b_check(string $identifierColumnB): bool|string {
        $child = $_SESSION['evo_wizard']['child_module'] ?? '';
        if (!$this->model->identifier_columns_valid($identifierColumnB, $child)) {
            return 'Identifier column B contains a column that does not exist on the ' . $child . ' table.';
        }
        return true;
    }

    /**
     * Generates the relation from wizard session data.
     *
     * Runs the full eligibility pre-flight (fail loudly — nothing half-done),
     * builds + executes the schema SQL FIRST, and writes the settings JSON
     * (the runtime contract) only AFTER the schema exists. Any failure →
     * evo render_generation_error, with the settings file removed in the
     * catch so a retry is never blocked by a stale "relation exists" file.
     *
     * @return void
     */
    public function run_gen(): void {

        $wizard = $_SESSION['evo_wizard'] ?? [];
        $type = $wizard['relation_type'] ?? '';
        $parent = $wizard['parent_module'] ?? '';
        $child = $wizard['child_module'] ?? '';

        if ($type === '' || $parent === '' || $child === '') {
            $this->evo->render_generation_error('Relation could not be created because the wizard data is incomplete.');
            return;
        }

        try {
            // 1. Pre-flight guards — every check BEFORE any mutation.
            $this->model->guard_relation_ready($wizard);

            // 1b. Injection pre-flight — every injection anchor verified
            //     BEFORE any write (same atomicity as the rest of run_gen).
            $this->preflight($wizard);

            // 2. Schema SQL — built and executed first (one statement per
            //    Db::query call — no multi-statement strings). A SQL
            //    failure here leaves no settings file behind.
            $sql = $this->model->build_relation_sql($wizard);
            foreach ($sql as $statement) {
                $this->db->query($statement);
            }

            // 3. Settings JSON — the runtime contract, written only after
            //    the schema exists (never points at a missing schema).
            $this->model->write_settings_file($wizard);
            $settings_written = true;

            // 4. Create-form + panel injection (after the schema +
            //    settings exist — the runtime contract is complete before
            //    any generated code references it).
            $this->apply($wizard);
        } catch (\Throwable $e) {
            // Never leave a settings file pointing at a schema that does
            // not exist — a retry would otherwise fail with "relation
            // already exists" (the settings file is the existence marker).
            // Only delete when THIS run wrote it: a failure in the guards
            // (e.g. re-running generation on an existing relation) must
            // never destroy the existing relation's contract.
            if (!empty($settings_written)) {
                $this->model->delete_settings_file($wizard);
            }
            $this->evo->render_generation_error($e->getMessage());
            return;
        }

        $data['view_module'] = 'trongate_control/module_relations_builder';
        $data['parent_label'] = ucwords(str_replace('_', ' ', $parent));
        $data['child_label'] = ucwords(str_replace('_', ' ', $child));
        $data['module_a_manage_url'] = BASE_URL . $parent . '/manage';
        $data['module_b_manage_url'] = BASE_URL . $child . '/manage';
        $this->view('generate_relation', $data);
    }

    // ─── Injection: render helper ──────────────────────────────

    /**
     * Render a code template (views/injectables/*.php) to a string, decoding
     * entity-escaped PHP tags (the site_builder prep_file_contents pattern).
     * Templates are the single home for generated content; this controller
     * is the only renderer (models cannot call view()).
     *
     * @param string $view Template name (without .php, under views/injectables/).
     * @param array  $data View data.
     * @return string Rendered, decoded block.
     */
    private function render_code_block(string $view, array $data): string {
        $data['view_module'] = 'trongate_control/module_relations_builder';
        $html = $this->view('injectables/' . $view, $data, true);
        // Template files may carry CRLF on Windows checkouts; blocks are
        // spliced into LF-normalized targets, so normalize the rendered
        // block too — one consistent EOL in every written file.
        $html = $this->normalize_line_endings($html);
        return $this->model->prep_file_contents($html);
    }

    // ─── Injection: plan builders ──────────────────────────────

    /**
     * Build the injection plan for a relation, per type.
     *
     * EVERY relation type also wires the show-page summary panel into BOTH
     * modules' show views (runtime — the association UI). Create-form
     * dropdowns are added per type: both sides for 1:1 no-bridge, the
     * child only for 1:N, and none for junction types (1:1 bridge / N:N —
     * the show-page panel is the association surface there). For 1:N the
     * PARENT additionally receives a children-release delete hook (no
     * orphans — children must never point at a deleted parent). For N:N
     * BOTH modules receive a junction-row release delete hook (no orphans —
     * a deleted record must not leave links behind in the junction table).
     *
     * @param array $wizard The wizard session array.
     * @return array<int, array> Injection items.
     */
    private function build_plan(array $wizard): array {
        $type = $wizard['relation_type'] ?? '';
        $module_a = $wizard['parent_module'] ?? '';
        $module_b = $wizard['child_module'] ?? '';
        $singular_a = $wizard['singular_a'] ?? '';
        $singular_b = $wizard['singular_b'] ?? '';
        $bridging = (bool) ($wizard['bridging_table'] ?? false);

        $panels = array_merge(
            $this->plan_show_panel($module_a, $module_b),
            $this->plan_show_panel($module_b, $module_a)
        );

        if (($type === 'one to one') && !$bridging) {
            return array_merge(
                $this->plan_form_module($module_a, $module_b, $singular_b, true),
                $this->plan_form_module($module_b, $module_a, $singular_a, true),
                $panels
            );
        }

        if ($type === 'one to many') {
            // Child (module_b) create form with the parent dropdown; the
            // parent (module_a) gets a delete hook that releases its
            // children (no orphans — children must never point at a
            // deleted parent).
            $plural_b = $wizard['plural_b'] ?? $module_b;
            return array_merge(
                $this->plan_form_module($module_b, $module_a, $singular_a, false),
                $this->plan_parent_delete_hook($module_a, $module_b, $singular_a, $plural_b),
                $panels
            );
        }

        // one to one (bridge) — panels only, no create-form dropdowns
        // (documented non-goal): the show-page panel is the association
        // surface for junction relations.
        if ($type === 'one to one') {
            return $panels;
        }

        // many to many — panels (no create-form dropdowns, same as bridged
        // 1:1) PLUS junction-row delete hooks on BOTH modules: a deleted
        // record must not leave orphaned junction rows behind.
        return array_merge(
            $this->plan_junction_delete_hook($module_a, $module_b),
            $this->plan_junction_delete_hook($module_b, $module_a),
            $panels
        );
    }

    /**
     * Injection item for ONE module's show view: the module_relations
     * summary panel call (runtime — the association UI).
     *
     * Appended after the details card (bottom of the view) — the same
     * placement as the academy_awards reference layout, so the tall panel
     * never pushes the record details down the page. The panel reads the
     * calling module + record id from the URL segments, so the show view
     * needs no extra data plumbing.
     *
     * @param string $module     The module receiving injection.
     * @param string $alt_module The associated module (panel argument).
     * @return array<int, array> Injection items (exactly one).
     */
    private function plan_show_panel(string $module, string $alt_module): array {
        $call = rtrim($this->render_code_block('summary_panel', [
            'alt_module' => $alt_module,
            'runtime_route' => $this->model->get_runtime_route()
        ]));
        return [[
            'file' => 'modules/' . $module . '/views/show.php',
            'anchor' => self::END_OF_VIEW,
            'insert' => 'after',
            'block' => $call,
            'marker' => $call,
            'fk' => ''
        ]];
    }

    /**
     * Injection items for ONE module's create form + controller.
     *
     * @param string $module       The module receiving injection.
     * @param string $alt_module   The associated module (dropdown source).
     * @param string $alt_singular Singular name of the associated module.
     * @param bool   $with_sync    Whether to inject 1:1 sync + delete-hook
     *                             methods (true for direct 1:1, false for
     *                             1:N child).
     * @return array<int, array> Injection items.
     */
    private function plan_form_module(string $module, string $alt_module, string $alt_singular, bool $with_sync): array {
        $fk = $this->model->fk_name($alt_singular);
        $options_var = $this->model->options_var($alt_singular);
        $label = $this->model->label($alt_singular);
        $controller = 'modules/' . $module . '/' . ucfirst($module) . '.php';
        $create_view = 'modules/' . $module . '/views/create.php';
        $model = 'modules/' . $module . '/' . ucfirst($module) . '_model.php';
        $marker = $this->model->marker($fk);
        $plan = [];

        // 1. Controller create(): options fetch line (before view_file).
        $plan[] = [
            'file' => $controller,
            'anchor' => "\$data['view_file'] = 'create';",
            'insert' => 'before',
            'block' => $this->render_code_block('options_fetch', [
                'fk' => $fk,
                'options_var' => $options_var,
                'alt_singular' => $alt_singular,
                'marker' => $marker
            ]),
            'marker' => '// ' . $marker . 'options fetch',
            'fk' => $fk
        ];

        // 2. Controller: get_{alt_singular}_options() private method.
        $plan[] = [
            'file' => $controller,
            'anchor' => self::END_OF_CLASS,
            'insert' => 'before',
            'block' => $this->render_code_block('get_options_method', [
                'alt_singular' => $alt_singular,
                'label' => $label,
                'module' => $module,
                'alt_module' => $alt_module,
                'runtime_route' => $this->model->get_runtime_route()
            ]),
            'marker' => 'private function get_' . $alt_singular . '_options(',
            'fk' => $fk
        ];

        // 3. Create view: dropdown row (replacing any existing FK field row).
        $plan[] = [
            'file' => $create_view,
            'anchor' => "echo '<div class=\"text-center\">';",
            'insert' => 'before',
            'block' => $this->render_code_block('dropdown_row', [
                'fk' => $fk,
                'options_var' => $options_var,
                'label' => $label,
                'marker' => $marker
            ]),
            'marker' => '// ' . $marker . 'dropdown row',
            'fk' => $fk,
            'remove_existing' => true
        ];

        // 4. Model get_data_from_post(): FK line — defaults to 0, never
        //    required (a create without a pick stores 0; the 0 sentinel
        //    means "unclaimed", matching the schema default refs #14).
        $plan[] = [
            'file' => $model,
            'anchor' => "public function get_data_from_post(): array {\n        return [",
            'insert' => 'after',
            'block' => $this->render_code_block('posted_data', [
                'fk' => $fk,
                'marker' => $marker
            ]),
            'marker' => '// ' . $marker . 'posted data',
            'fk' => $fk
        ];

        if ($with_sync) {
            // 5. Controller submit(): capture the pre-edit FK before the
            //    UPDATE overwrites it (needed to release the old partner).
            $plan[] = [
                'file' => $controller,
                'anchor' => "\$this->model->update_record(\$update_id, \$data);",
                'insert' => 'before',
                'block' => $this->render_code_block('prev_capture', ['fk' => $fk]),
                'marker' => '// ' . $marker . 'prev capture',
                'fk' => $fk
            ];

            // 6. Controller submit(): sync after create.
            $plan[] = [
                'file' => $controller,
                'anchor' => "\$update_id = \$this->model->create_new_record(\$data);",
                'insert' => 'after',
                'block' => $this->render_code_block('sync_on_save_create', [
                    'alt_singular' => $alt_singular,
                    'fk' => $fk
                ]),
                'marker' => '// ' . $marker . 'sync on save',
                'fk' => $fk
            ];

            // 7. Controller submit(): sync after update.
            $plan[] = [
                'file' => $controller,
                'anchor' => "\$this->model->update_record(\$update_id, \$data);",
                'insert' => 'after',
                'block' => $this->render_code_block('sync_on_save_update', [
                    'alt_singular' => $alt_singular,
                    'fk' => $fk
                ]),
                'marker' => '// ' . $marker . 'sync on save',
                'fk' => $fk
            ];

            // 8. Controller: sync_with_{alt_singular}() private method.
            $plan[] = [
                'file' => $controller,
                'anchor' => self::END_OF_CLASS,
                'insert' => 'before',
                'block' => $this->render_code_block('sync_method', [
                    'alt_singular' => $alt_singular,
                    'module' => $module,
                    'alt_module' => $alt_module,
                    'runtime_route' => $this->model->get_runtime_route()
                ]),
                'marker' => 'private function sync_with_' . $alt_singular . '(',
                'fk' => $fk
            ];

            // 9. Controller submit_delete(): release hook before delete.
            $plan[] = [
                'file' => $controller,
                'anchor' => "\$this->model->delete_record(\$update_id);",
                'insert' => 'before',
                'block' => $this->render_code_block('delete_hook', [
                    'alt_singular' => $alt_singular,
                    'fk' => $fk
                ]),
                'marker' => '// ' . $marker . 'delete hook',
                'fk' => $fk
            ];

            // 10. Controller: release_{alt_singular}_link() private method.
            $plan[] = [
                'file' => $controller,
                'anchor' => self::END_OF_CLASS,
                'insert' => 'before',
                'block' => $this->render_code_block('release_method', [
                    'alt_singular' => $alt_singular,
                    'module' => $module,
                    'alt_module' => $alt_module,
                    'runtime_route' => $this->model->get_runtime_route()
                ]),
                'marker' => 'private function release_' . $alt_singular . '_link(',
                'fk' => $fk
            ];
        }

        return $plan;
    }

    /**
     * Injection items for the PARENT module of a one-to-many relation:
     * the delete hook that releases its children (no orphans).
     *
     * Two items: the release call injected into submit_delete() before
     * the record is removed, and the release_{plural}_links() private
     * method that delegates to the module_relations runtime. The FK
     * marker uses the PARENT's own singular name — the FK column that
     * lives on the CHILD table, pointing back at the parent.
     *
     * @param string $module       The parent module receiving injection.
     * @param string $child_module The child module (runtime alt_module).
     * @param string $singular     The parent module's singular name.
     * @param string $plural       The child module's plural name.
     * @return array<int, array> Injection items.
     */
    private function plan_parent_delete_hook(string $module, string $child_module, string $singular, string $plural): array {
        $fk = $this->model->fk_name($singular);
        $marker = $this->model->marker($fk);
        $controller = 'modules/' . $module . '/' . ucfirst($module) . '.php';

        return [
            [
                'file' => $controller,
                'anchor' => "\$this->model->delete_record(\$update_id);",
                'insert' => 'before',
                'block' => $this->render_code_block('parent_delete_hook', [
                    'fk' => $fk,
                    'plural' => $plural
                ]),
                'marker' => '// ' . $marker . 'delete hook',
                'fk' => $fk
            ],
            [
                'file' => $controller,
                'anchor' => self::END_OF_CLASS,
                'insert' => 'before',
                'block' => $this->render_code_block('release_children_method', [
                    'plural' => $plural,
                    'module' => $module,
                    'alt_module' => $child_module,
                    'runtime_route' => $this->model->get_runtime_route()
                ]),
                'marker' => 'private function release_' . $plural . '_links(',
                'fk' => ''
            ]
        ];
    }

    /**
     * Injection items for ONE module of a many-to-many relation: the
     * delete hook that removes the record's junction rows (no orphans —
     * a deleted record must not leave links behind).
     *
     * Two items: the release call injected into submit_delete() before
     * the record is removed, and the release_junction_links() private
     * method that delegates to the module_relations runtime. The marker
     * is fixed — a junction relation has no FK column of its own (the
     * release is symmetric across both sides).
     *
     * @param string $module     The module receiving injection.
     * @param string $alt_module The other module in the relation (runtime
     *                           alt_module).
     * @return array<int, array> Injection items.
     */
    private function plan_junction_delete_hook(string $module, string $alt_module): array {
        $controller = 'modules/' . $module . '/' . ucfirst($module) . '.php';

        return [
            [
                'file' => $controller,
                'anchor' => "\$this->model->delete_record(\$update_id);",
                'insert' => 'before',
                'block' => $this->render_code_block('junction_delete_hook', []),
                'marker' => '// flo_relation: junction delete hook',
                'fk' => ''
            ],
            [
                'file' => $controller,
                'anchor' => self::END_OF_CLASS,
                'insert' => 'before',
                'block' => $this->render_code_block('release_junction_method', [
                    'module' => $module,
                    'alt_module' => $alt_module,
                    'runtime_route' => $this->model->get_runtime_route()
                ]),
                'marker' => 'private function release_junction_links(',
                'fk' => ''
            ]
        ];
    }

    // ─── Injection: preflight ──────────────────────────────────

    /**
     * Pre-flight every injection target BEFORE any write.
     *
     * Checks, for every plan item: target file exists, anchor found exactly
     * once (or the file ends with the class-closing brace), no marker
     * already present, file writable. Any miss throws — nothing is written.
     *
     * @param array $wizard The wizard session array.
     * @return void
     * @throws \Exception On the first failed check (nothing written).
     */
    private function preflight(array $wizard): void {
        $plan = $this->build_plan($wizard);

        foreach ($plan as $item) {
            $path = APPPATH . $item['file'];
            if (!is_file($path)) {
                throw new \Exception('Injection aborted: target file does not exist — ' . $item['file']);
            }
            $raw = file_get_contents($path);
            if ($raw === false) {
                throw new \Exception('Injection aborted: target file could not be read — ' . $item['file']);
            }
            // Normalize EOLs to LF: anchors and regexes are LF-based, so
            // CRLF checkouts (Windows) would never match. Raw bytes are
            // kept for rollback (see apply()).
            $content = $this->normalize_line_endings($raw);

            if ($item['anchor'] === self::END_OF_CLASS) {
                if (rtrim($content) === '' || substr(rtrim($content), -1) !== '}') {
                    throw new \Exception('Injection aborted: class-closing brace not found at the end of ' . $item['file'] . '.');
                }
            } elseif ($item['anchor'] === self::END_OF_VIEW) {
                // Marker check first — a previously-injected view ends with
                // the panel call (not </div>), so "already present" is the
                // accurate refusal over the card-tail check below.
                if ($item['marker'] !== '' && strpos($content, $item['marker']) !== false) {
                    throw new \Exception('Injection refused: "' . $item['marker'] . '" is already present in ' . $item['file'] . ' — never double-inject.');
                }
                if (rtrim($content) === '' || substr(rtrim($content), -6) !== '</div>') {
                    throw new \Exception('Injection aborted: show view does not end with the details card (</div>) — ' . $item['file'] . '.');
                }
            } else {
                $count = substr_count($content, $item['anchor']);
                if ($count !== 1) {
                    throw new \Exception('Injection aborted: anchor "' . $this->excerpt($item['anchor']) . '" found ' . $count . ' time(s) in ' . $item['file'] . ' (expected exactly 1).');
                }
            }

            if ($item['marker'] !== '' && strpos($content, $item['marker']) !== false) {
                throw new \Exception('Injection refused: "' . $item['marker'] . '" is already present in ' . $item['file'] . ' — never double-inject.');
            }

            if (!is_writable($path)) {
                throw new \Exception('Injection aborted: target file is not writable — ' . $item['file']);
            }

            // Defensive replace check: an existing field row for the FK
            // must be cleanly removable (replace, never stack).
            if (!empty($item['remove_existing']) && $this->has_field_call($content, $item['fk'])) {
                if (preg_match($this->field_row_pattern($item['fk']), $content) !== 1) {
                    throw new \Exception('Injection aborted: an existing field row for "' . $item['fk'] . '" in ' . $item['file'] . ' could not be cleanly replaced.');
                }
            }
        }
    }

    // ─── Injection: apply ──────────────────────────────────────

    /**
     * Apply the injections for a relation.
     *
     * Computes the full new content for every target file in memory FIRST,
     * then writes each file; a write failure rolls back the files already
     * written (near-atomic — nothing half-done).
     *
     * @param array $wizard The wizard session array.
     * @return void
     * @throws \Exception On write failure (after rollback).
     */
    private function apply(array $wizard): void {
        $plan = $this->build_plan($wizard);

        $originals = [];
        $updates = [];
        foreach ($plan as $item) {
            $path = APPPATH . $item['file'];
            if (!isset($originals[$path])) {
                // Keep the RAW bytes (possibly CRLF) — rollback must
                // restore the file exactly as it was on disk.
                $originals[$path] = file_get_contents($path);
            }
            if (!isset($updates[$path])) {
                $updates[$path] = $this->normalize_line_endings($originals[$path]);
            }
            $updates[$path] = $this->apply_item($updates[$path], $item);
        }

        $written = [];
        foreach ($updates as $path => $new_content) {
            if (file_put_contents($path, $new_content) === false) {
                // Roll back the files already written this run.
                foreach ($written as $p) {
                    @file_put_contents($p, $originals[$p]);
                }
                throw new \Exception('Injection failed: could not write ' . str_replace(APPPATH, '', $path) . '. No files were left half-injected.');
            }
            $written[] = $path;
        }
    }

    /**
     * Normalize CRLF/CR line endings to LF.
     *
     * Injection anchors, block templates and field-row regexes are all
     * LF-based, so target files must be read as LF regardless of the
     * checkout's EOL style (Windows checkouts are CRLF by default).
     * Applied at read time only — apply() keeps the raw originals for
     * rollback, and the written result is a single consistent EOL.
     *
     * @param string $content The file content as read from disk.
     * @return string The content with every line ending normalized to LF.
     */
    private function normalize_line_endings(string $content): string {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * Apply a single injection item to file content.
     *
     * END_OF_CLASS and END_OF_VIEW anchors get their own insert modes
     * (before the class-closing brace / after the final </div>). Other
     * items insert before or after the anchor line; 'before' is
     * line-granular — the WHOLE anchor line (including its leading
     * whitespace) is replaced with the block followed by the original line,
     * so the anchor keeps its own indentation and the block's baked-in
     * indentation is never doubled.
     *
     * @param string $content The current file content.
     * @param array  $item    The injection item.
     * @return string The updated content.
     */
    private function apply_item(string $content, array $item): string {
        if (!empty($item['remove_existing'])) {
            $content = $this->remove_existing_field_row($content, $item['fk']);
        }

        if ($item['anchor'] === self::END_OF_CLASS) {
            $trimmed = rtrim($content);
            return substr($trimmed, 0, -1) . "\n" . rtrim($item['block']) . "\n}\n";
        }

        if ($item['anchor'] === self::END_OF_VIEW) {
            return rtrim($content) . "\n\n" . rtrim($item['block']) . "\n";
        }

        if (($item['insert'] ?? 'before') === 'after') {
            return str_replace($item['anchor'], $item['anchor'] . "\n" . rtrim($item['block']), $content);
        }

        // 'before' insertion, line-granular: replace the WHOLE anchor line
        // (including its leading whitespace) with the block followed by the
        // original line — the anchor keeps its own indentation and the
        // block's baked-in indentation is never doubled.
        $pos = strpos($content, $item['anchor']);
        $prev_nl = strrpos(substr($content, 0, $pos), "\n");
        $line_start = ($prev_nl === false) ? 0 : $prev_nl + 1;
        $line_end = strpos($content, "\n", $pos);
        $line_end = ($line_end === false) ? strlen($content) : $line_end;
        $line = substr($content, $line_start, $line_end - $line_start);

        return substr($content, 0, $line_start) . rtrim($item['block']) . "\n" . $line . substr($content, $line_end);
    }

    /**
     * Remove a donor-generated field row for the FK column (defensive
     * replace — the wizard's guards normally prevent pre-existing FK
     * columns, so this is usually a no-op).
     *
     * @param string $content The create view content.
     * @param string $fk      The FK field name.
     * @return string The updated content.
     */
    private function remove_existing_field_row(string $content, string $fk): string {
        $pattern = $this->field_row_pattern($fk);
        return preg_replace($pattern, '', $content, 1);
    }

    /**
     * Regex matching a donor-generated field row for the FK: its label
     * line, optional attr block, and the form_*() call itself.
     *
     * @param string $fk The FK field name.
     * @return string The regex (with delimiters + /s flag).
     */
    private function field_row_pattern(string $fk): string {
        $fk_quoted = preg_quote($fk, '/');
        return "/echo form_label\\('[^']*'\\)\n(?:        \\$" . $fk_quoted . "_attr = \\[.*?\\];\n)?        echo form_\\w+\\('" . $fk_quoted . "'/s";
    }

    /**
     * Whether the content contains any form_*() call for the FK field.
     *
     * @param string $content The create view content.
     * @param string $fk      The FK field name.
     * @return bool True when a field call exists.
     */
    private function has_field_call(string $content, string $fk): bool {
        $fk_quoted = preg_quote($fk, '/');
        return preg_match("/form_\\w+\\('" . $fk_quoted . "'/", $content) === 1;
    }

    /**
     * Short, printable excerpt of an anchor for error messages.
     *
     * @param string $anchor The anchor string.
     * @return string A single-line excerpt.
     */
    private function excerpt(string $anchor): string {
        $one_line = str_replace("\n", ' ', $anchor);
        return (strlen($one_line) > 60) ? substr($one_line, 0, 57) . '...' : $one_line;
    }

}
