<?php
/**
 * Image_uploader_builder - the wizard that installs a single-image uploader
 * into an existing app module.
 *
 * Walks the developer through (choose module → feature details → generate),
 * then, on run_gen(), performs the generation in two phases: plan and
 * validate everything in memory, then commit — an idempotent ALTER TABLE,
 * the settings JSON write and the code injection.
 *
 * The injected code is what makes the feature visible: the show page calls
 * the runtime's draw_panel(), and submit_delete() captures the picture file
 * name before the row is deleted and removes the files after it has gone.
 * The runtime counterpart lives in the top-level image_uploader module;
 * this controller writes the schema, the settings JSON (the runtime
 * contract) and the two call sites that reach it.
 *
 * Generation-time only: the wizard refuses to run unless ENV is 'dev'.
 *
 * NOTE ON METHOD NAMES: no public method may contain the module-assets
 * trigger ('_module', see MODULE_ASSETS_TRIGGER in engine/Core.php) as a
 * substring — the router would treat the URL as a module asset request
 * instead of routing it. Hence choose_mod() and submit_mod().
 */
class Image_uploader_builder extends Trongate {

    /**
     * Default settings applied to a newly chosen module.
     */
    private const DEFAULTS = [
        'column' => 'picture',
        'destination' => 'uploads',
        'max_size' => 2000,
        'max_width' => 1200,
        'max_height' => 1200,
        'resize_max_width' => 450,
        'resize_max_height' => 450,
        'thumbnails' => true,
        'thumbnail_max_width' => 120,
        'thumbnail_max_height' => 120
    ];

    /**
     * Sentinel anchor: append after the final closing </div> of a view.
     *
     * The same convention the module_relations_builder wizard uses. A
     * generated show view ends with the details card, so that card's
     * closing </div> marks the panel call's home.
     */
    private const END_OF_VIEW = '@end_of_view';

    /**
     * The single row-deletion choke point that every generated controller's
     * submit_delete() contains. The capture block is injected BEFORE this
     * line and the file-removal hook AFTER it: row first, files second.
     */
    private const DELETE_ANCHOR = '$this->model->delete_record($update_id);';

    /**
     * The scaffold line the capture block depends upon — the record (and so
     * its picture file name) must be loaded before the row is deleted.
     * Verified by preflight(), never assumed.
     */
    private const RECORD_LOAD = '$record = $this->model->find_by_id($update_id);';

    /**
     * Constructor — dev-mode guard, identical pattern to sibling child modules.
     *
     * Loads the generic Flo (evo) module for the shared utilities
     * (render_error(), render_generation_error(), render_disabled_response()).
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
     * Wizard entry point — renders the module chooser.
     *
     * Clears any stale wizard session state so every run starts fresh. The
     * chooser offers the candidate modules that pass the CRUD preflight;
     * modules that cannot take an uploader are simply not offered (the same
     * plain chooser the sibling wizards render).
     *
     * @return void
     */
    public function choose_mod(): void {
        unset($_SESSION['evo_wizard']);
        $data['view_module'] = 'trongate_control/image_uploader_builder';
        $data['modules'] = $this->model->get_crud_modules();
        $this->view('choose_mod', $data);
    }

    /**
     * Handle the posted module selection and render the confirmation step.
     *
     * The selection is never trusted: get_eligible_module() recomputes the
     * ready list server-side and returns null unless the posted name is in
     * it. A module that already has a settings file is refused with a
     * friendly notice — that file is the "already has an uploader" marker.
     *
     * On success the wizard session is seeded with the module, its default
     * table name and the default settings, and the generate step is drawn.
     *
     * @return void
     */
    public function submit_mod(): void {

        $selected = post('selected', true);
        $module = $this->model->get_eligible_module($selected);

        if ($module === null) {
            echo $this->evo->render_error('Please choose a module that is ready for an image uploader.');
            return;
        } elseif ($this->model->uploader_exists($module)) {
            $error_msg = str_replace('_', ' ', $module).' Already Has an Image Uploader';
            echo $this->evo->render_error($error_msg);
            return;
        }

        $_SESSION['evo_wizard']['module'] = $module;
        $_SESSION['evo_wizard']['table'] = $module; // default table name (overridable)
        $_SESSION['evo_wizard'] = array_merge($_SESSION['evo_wizard'], self::DEFAULTS);

        $data['view_module'] = 'trongate_control/image_uploader_builder';
        $this->view('conf_generate_uploader', $data);
    }

    /**
     * Render the uploader details step.
     *
     * Two render paths, fed from the same data: '/web' renders as a full
     * page inside evo's shared details iframe (the review step opened from
     * the confirmation screen), otherwise it renders in-page. Both paths
     * seed localStorage from the wizard session, so the view always renders
     * with defined values.
     *
     * @return void
     */
    public function uploader_details(): void {
        $wizard = $_SESSION['evo_wizard'] ?? [];

        $data['view_module'] = 'trongate_control/image_uploader_builder';
        $data['wizard'] = $wizard;
        $data['page_title'] = 'Image Uploader Details';
        $data['after_close_url'] = BASE_URL . 'trongate_control-image_uploader_builder/conf_generate_uploader';
        $data['after_close_width'] = 800;
        $data['after_close_height'] = 600;

        if (segment(3) === 'web') {
            $view_content = $this->view('conf_uploader_details', $data, true);

            // Seed localStorage from session data for the view's JS.
            $data['view_content'] = $view_content;
            $data['local_storage_items'] = [
                'column' => $wizard['column'] ?? self::DEFAULTS['column'],
                'table' => $wizard['table'] ?? ($wizard['module'] ?? ''),
                'destination' => $wizard['destination'] ?? self::DEFAULTS['destination'],
                'max_size' => $wizard['max_size'] ?? self::DEFAULTS['max_size'],
                'max_width' => $wizard['max_width'] ?? self::DEFAULTS['max_width'],
                'max_height' => $wizard['max_height'] ?? self::DEFAULTS['max_height'],
                'resize_max_width' => $wizard['resize_max_width'] ?? self::DEFAULTS['resize_max_width'],
                'resize_max_height' => $wizard['resize_max_height'] ?? self::DEFAULTS['resize_max_height'],
                'thumbnail_max_width' => $wizard['thumbnail_max_width'] ?? self::DEFAULTS['thumbnail_max_width'],
                'thumbnail_max_height' => $wizard['thumbnail_max_height'] ?? self::DEFAULTS['thumbnail_max_height'],
                'thumbnails' => ($wizard['thumbnails'] ?? self::DEFAULTS['thumbnails']) ? 'Yes' : 'No'
            ];

            $this->evo->render_details_iframe($data);
        } else {
            $this->view('conf_uploader_details', $data);
        }
    }

    /**
     * Generate the uploader: schema, settings JSON, then code injection.
     *
     * Refuses to run when the wizard session is incomplete or when the
     * module already has a settings file (the duplicate-feature marker).
     * The work itself is split into two phases:
     *
     *   1. Plan and validate — the model's preflight guards plus
     *      preflight(), which composes the exact new content of every target
     *      file and parse-checks it. All in memory: a failure here leaves the
     *      module completely untouched.
     *   2. Commit — the idempotent ALTER TABLE, the settings JSON (the
     *      runtime contract and the marker), then commit(), which writes the
     *      injected code last so a generated module never calls the runtime
     *      before the runtime knows about it.
     *
     * A failure after the settings file was written deletes it, so no marker
     * survives a half-finished install. commit() rolls back its own files;
     * the schema step is deliberately left in place — see
     * partial_state_note().
     *
     * @return void
     */
    public function run_gen(): void {

        $wizard = $_SESSION['evo_wizard'] ?? [];
        $module = $wizard['module'] ?? '';

        if (($module === '') || ($wizard['table'] ?? '') === '' || ($wizard['column'] ?? '') === '') {
            echo $this->evo->render_error('The image uploader could not be created because the wizard data is incomplete.');
            return;
        }

        if ($this->model->uploader_exists($module)) {
            $error_msg = str_replace('_', ' ', $module).' Already Has an Image Uploader';
            echo $this->evo->render_error($error_msg);
            return;
        }

        $settings_written = false;
        $alter_message = null;

        try {

            $this->model->guard_uploader_ready($wizard);
            $plan = $this->preflight($wizard);
            $alter_message = $this->model->apply_column($wizard);

            $settings_path = $this->model->write_settings_file($wizard);
            $settings_written = true;
            $this->commit($plan);
        } catch (\Throwable $e) {
            if ($settings_written) {
                $this->model->delete_settings_file($module);
            }
            $this->evo->render_generation_error($e->getMessage() . $this->partial_state_note($alter_message));
            return;
        }

        $data['view_module'] = 'trongate_control/image_uploader_builder';
        $data['wizard'] = $_SESSION['evo_wizard'];
        $data['alter_message'] = $alter_message;
        $data['settings_path'] = $settings_path;
        $data['module_label'] = ucwords(str_replace('_', ' ', $module));
        $data['module_url'] = BASE_URL . $module . '/manage';

        $this->view('generate_result', $data);
    }

    /**
     * Render one of the code templates (views/injectables/*.php) to a string.
     *
     * Entity-escaped PHP tags in the template are decoded back to real tags
     * (the site_builder prep_file_contents pattern). The templates are the
     * single home for generated content, and this controller is their only
     * renderer, because models cannot call view().
     *
     * Leading blank lines are stripped so the block's first line keeps its
     * baked-in indentation without adding empty lines at the insertion point.
     *
     * @param string $view Template name, without .php (under views/injectables/).
     * @param array  $data View data.
     * @return string The rendered, decoded block.
     */
    private function render_code_block(string $view, array $data): string {
        $data['view_module'] = 'trongate_control/image_uploader_builder';
        $html = $this->view('injectables/' . $view, $data, true);
        return rtrim($this->model->prep_file_contents(ltrim($html, "\r\n")));
    }

    /**
     * Build the injection plan for a module.
     *
     * Three items, always — the runtime's whole reach into a generated
     * module:
     *
     *   1. views/show.php — the draw_panel() call, appended after the
     *                       details card.
     *   2. {Module}.php   — the capture line, inserted BEFORE the row
     *                       deletion in submit_delete().
     *   3. {Module}.php   — the file-removal call, inserted AFTER it.
     *
     * Each item carries the marker string that guards against
     * double-injection; for the panel call the marker IS the rendered call.
     *
     * Both anchors are properties of the wizard's own scaffold output rather
     * than guesses about a particular module: the show view is generated to
     * end with the details card's closing </div>, and submit_delete()
     * contains one $record load and one row-deletion call. Anything else is
     * refused by preflight(), never worked around.
     *
     * @param array $wizard The wizard session array.
     * @return array<int, array> The injection items.
     */
    private function build_plan(array $wizard): array {
        $module = $wizard['module'] ?? '';
        $column = $wizard['column'] ?? '';
        $controller = 'modules/' . $module . '/' . ucfirst($module) . '.php';

        $panel_call = $this->render_code_block('panel_call', ['module' => $module]);
        $capture = $this->render_code_block('delete_capture', ['column' => $column]);
        $hook = $this->render_code_block('delete_hook', ['module' => $module]);

        return [
            [
                'file' => 'modules/' . $module . '/views/show.php',
                'anchor' => self::END_OF_VIEW,
                'insert' => 'after',
                'block' => $panel_call,
                'marker' => $panel_call,
                'needs_record' => false,
                'label' => 'Show page — Picture panel call'
            ],
            [
                'file' => $controller,
                'anchor' => self::DELETE_ANCHOR,
                'insert' => 'before',
                'block' => $capture,
                'marker' => '// Image uploader delete hook: capture the picture file name',
                'needs_record' => true,
                'label' => 'Controller — capture the file name before deletion'
            ],
            [
                'file' => $controller,
                'anchor' => self::DELETE_ANCHOR,
                'insert' => 'after',
                'block' => $hook,
                'marker' => '// Image uploader delete hook: remove the recorded files',
                'needs_record' => false,
                'label' => 'Controller — remove the files after deletion'
            ]
        ];
    }

    /**
     * Validate every injection target and compose the exact new file
     * contents — all in memory, before anything is written.
     *
     * Fails loudly on: a missing or unreadable target, an anchor that is
     * absent or ambiguous, a marker that is already present (never
     * double-inject), an unwritable file, a delete flow that does not match
     * the supported scaffold (the capture block reads $record), or generated
     * content that would not parse.
     *
     * Anchors and markers are always tested against the ORIGINAL file, and
     * the parse gate runs against the same bytes commit() will write — so
     * the check applies to the real output, not to a re-rendered
     * approximation of it.
     *
     * @param array $wizard The wizard session array.
     * @return array{items: array<int, array>, originals: array<string, string>, updates: array<string, string>} The validated plan.
     * @throws \Exception On the first failed check (nothing is written).
     */
    private function preflight(array $wizard): array {
        $items = $this->build_plan($wizard);

        $originals = [];
        $updates = [];

        foreach ($items as $item) {
            $path = APPPATH . $item['file'];

            if (!isset($originals[$path])) {

                if (!is_file($path)) {
                    throw new \Exception('Injection aborted: target file does not exist — ' . $item['file']);
                }

                $content = file_get_contents($path);

                if ($content === false) {
                    throw new \Exception('Injection aborted: target file could not be read — ' . $item['file']);
                }

                if (!is_writable($path)) {
                    throw new \Exception('Injection aborted: target file is not writable — ' . $item['file'] . ' (run: chmod 0666 ' . $path . ')');
                }

                $originals[$path] = $content;
                $updates[$path] = $content;
            }

            $content = $originals[$path];

            if ($item['anchor'] === self::END_OF_VIEW) {

                if (($item['marker'] !== '') && (strpos($content, $item['marker']) !== false)) {
                    throw new \Exception('Injection refused: "' . $this->excerpt($item['marker']) . '" is already present in ' . $item['file'] . ' — never double-inject.');
                }
                if (rtrim($content) === '' || substr(rtrim($content), -6) !== '</div>') {
                    throw new \Exception('Injection aborted: the show view does not end with the details card (</div>) — ' . $item['file'] . '. The panel call is appended after that card; adjust the view or wire the panel manually.');
                }
            } else {
                $count = substr_count($content, $item['anchor']);
                if ($count !== 1) {
                    throw new \Exception('Injection aborted: anchor "' . $this->excerpt($item['anchor']) . '" found ' . $count . ' time(s) in ' . $item['file'] . ' (expected exactly 1).');
                }
            }

            if (($item['marker'] !== '') && (strpos($content, $item['marker']) !== false)) {
                throw new \Exception('Injection refused: "' . $this->excerpt($item['marker']) . '" is already present in ' . $item['file'] . ' — never double-inject.');
            }

            if ($item['needs_record'] === true) {
                $count = substr_count($content, self::RECORD_LOAD);
                if ($count !== 1) {
                    throw new \Exception('Injection aborted: the delete flow in ' . $item['file'] . ' does not match the supported scaffold — expected exactly one "' . self::RECORD_LOAD . '" line, found ' . $count . '. Wire the delete hook manually.');
                }
            }

            $updates[$path] = $this->apply_item($updates[$path], $item);
        }

        foreach ($updates as $path => $new_content) {
            $this->assert_parses($new_content, str_replace(APPPATH, '', $path));
        }

        return ['items' => $items, 'originals' => $originals, 'updates' => $updates];
    }

    /**
     * Write a validated plan to disk.
     *
     * preflight() has already composed and parse-checked every byte, so this
     * method renders nothing and validates nothing: it writes, and if a
     * write fails it restores the files written by THIS run. Nothing is ever
     * left half-injected.
     *
     * @param array $plan The plan returned by preflight().
     * @return void
     * @throws \Exception On write failure, after the rollback.
     */
    private function commit(array $plan): void {
        $written = [];

        foreach ($plan['updates'] as $path => $new_content) {
            if (file_put_contents($path, $new_content) === false) {
                foreach ($written as $p) {
                    @file_put_contents($p, $plan['originals'][$p]);
                }
                throw new \Exception('Injection failed: could not write ' . str_replace(APPPATH, '', $path) . '. Every file written by this run has been restored.');
            }
            $written[] = $path;
        }
    }

    /**
     * Honest footnote for a failure that happened after the ALTER TABLE.
     *
     * The ALTER is the one step with no safe undo — dropping a column is far
     * more dangerous than leaving an unused one — so a failure inside the
     * commit phase leaves the column in place. That is benign: the ALTER is
     * idempotent, so re-running the wizard skips it and proceeds. Say so,
     * rather than implying a perfect rollback.
     *
     * @param string|null $alter_message The ALTER outcome, or null when the ALTER never ran.
     * @return string A sentence to append to the error, or '' when nothing was committed.
     */
    private function partial_state_note(?string $alter_message): string {
        if ($alter_message === null) {
            return '';
        }
        return ' The schema step had already run, so the picture column is left in place — that is harmless, and re-running the wizard skips it.';
    }

    /**
     * Apply a single injection item to file content.
     *
     * END_OF_VIEW anchors append after the final </div>. Other items insert
     * before or after the anchor line; 'before' is line-granular — the WHOLE
     * anchor line (including its leading whitespace) is replaced with the
     * block followed by the original line, so the anchor keeps its own
     * indentation and the block's baked-in indentation is never doubled. A
     * blank line separates the injected block from the surrounding code.
     *
     * @param string $content The current file content.
     * @param array  $item    The injection item.
     * @return string The updated content.
     */
    private function apply_item(string $content, array $item): string {
        if ($item['anchor'] === self::END_OF_VIEW) {
            return rtrim($content) . "\n\n" . $item['block'] . "\n";
        }

        if (($item['insert'] ?? 'before') === 'after') {
            return str_replace($item['anchor'], $item['anchor'] . "\n\n" . $item['block'], $content);
        }

        $pos = strpos($content, $item['anchor']);
        $prev_nl = strrpos(substr($content, 0, $pos), "\n");
        $line_start = ($prev_nl === false) ? 0 : $prev_nl + 1;
        $line_end = strpos($content, "\n", $pos);
        $line_end = ($line_end === false) ? strlen($content) : $line_end;
        $line = substr($content, $line_start, $line_end - $line_start);

        return substr($content, 0, $line_start) . $item['block'] . "\n\n" . $line . substr($content, $line_end);
    }

    /**
     * Assert that generated file content is syntactically valid PHP.
     *
     * TOKEN_PARSE makes token_get_all() run PHP's real parser, which throws
     * ParseError on invalid syntax — an in-process equivalent of `php -l` for
     * the file about to be written. Inline HTML in a view is fine: the parser
     * treats it as inline text.
     *
     * @param string $code The full new file content.
     * @param string $file The relative file path (for the error message).
     * @return void
     * @throws \Exception When the content would not parse.
     */
    private function assert_parses(string $code, string $file): void {
        try {
            token_get_all($code, TOKEN_PARSE);
        } catch (\Throwable $e) {
            throw new \Exception('Injection aborted: the generated code for ' . $file . ' would not parse (' . $e->getMessage() . '). No files were changed.');
        }
    }

    /**
     * Short, printable excerpt of a string for error messages.
     *
     * @param string $text The string to shorten.
     * @return string A single-line excerpt.
     */
    private function excerpt(string $text): string {
        $one_line = str_replace("\n", ' ', $text);
        return (strlen($one_line) > 60) ? substr($one_line, 0, 57) . '...' : $one_line;
    }

}
