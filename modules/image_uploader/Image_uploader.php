<?php
/**
 * Image_uploader Controller — RUNTIME for generated single-image features.
 *
 * Generated application modules reach this controller via two paths:
 *
 *   1. Page load / Modules::run() — draw_panel() is called from a target
 *      module's show view (injected line), rendering the show-page
 *      "Picture" card for the record being viewed. delete_files() is
 *      called from the target module's delete hook after the row is gone.
 *   2. Browser / MX / plain page — render_panel_body() (mx-get with X-*
 *      headers) fills the card's live region; upload_form() is a dedicated
 *      multipart upload page; submit_upload() is its POST handler;
 *      remove_picture() is an MX POST with a CSRF token that clears the
 *      picture in place.
 *
 * The controller never writes schema and never knows a module's internals
 * ahead of time: it reads the per-module settings JSON that Flo's wizard
 * wrote (via Image_uploader_model::get_uploader_settings()) and operates
 * the feature it describes — the storage folder, the DB column, the
 * validation caps, the resize and thumbnail dimensions.
 *
 * Surface:
 *   draw_panel        — renders the show-page "Picture" card shell
 *                       (Modules::run, page load; block_url'd).
 *   render_panel_body — renders the card's live region: current picture or
 *                       placeholder + actions (browser, MX mx-get + X-*).
 *   upload_form       — dedicated upload page (plain multipart GET page).
 *   submit_upload     — POST handler: validates, uploads, updates the row,
 *                       POST → redirect → GET back to the record's page.
 *   remove_picture    — removes the picture + files in place (browser, MX
 *                       POST, CSRF-gated).
 *   delete_files      — delete hook helper: removes the recorded files for
 *                       a record whose row has already been deleted
 *                       (Modules::run, internal only; block_url'd).
 *
 * Auth posture: every endpoint that a browser can hit directly
 * (render_panel_body, upload_form, submit_upload, remove_picture) is
 * session-authenticated via trongate_security->make_sure_allowed(), and
 * both mutating endpoints are CSRF-gated via validation->run(). The two
 * Modules::run-only methods are block_url'd so a direct URL hit is
 * rejected. Panel rendering (draw_panel) inherits the gating of the page
 * that calls it, exactly like Module Relations' summary panel.
 */
class Image_uploader extends Trongate {

    /**
     * Constructor.
     *
     * @param string|null $module_name The module name (set by the framework).
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
    }

    // ============================================
    // Show-page panel (Modules::run from the target module's show view)
    // ============================================

    /**
     * Render the "Picture" card shell on a generated show page.
     *
     * Called via Modules::run() from the injected line in the target
     * module's show view, passing the module name. Renders nothing when no
     * uploader is configured for that module or when there is no record id
     * in the URL — the show page must look exactly as if this call had
     * never been made.
     *
     * The card is a shell (MRB pattern): it seeds a CSRF token when the
     * session has none, and hands the browser enough context (module,
     * record id and a fresh panel id) to fetch the live region via
     * render_panel_body().
     *
     * @param string|null $module_name The module whose record page this is.
     * @return void
     */
    public function draw_panel(?string $module_name = null): void {
        block_url('image_uploader/draw_panel');

        $module = strtolower((string) ($module_name ?? segment(1)));
        $update_id = (int) segment(3);

        $settings = $this->model->get_uploader_settings($module);

        if (($settings === null) || ($update_id === 0)) {
            return; // No uploader configured — render nothing.
        }

        // Seed the CSRF token when no page form has created one yet — the
        // panel's remove MX POST sends it via mx-vals and the endpoint
        // gates on validation->run().
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $panel_id = make_rand_str(16);

        $data['view_module'] = 'image_uploader';
        $data['module'] = $module;
        $data['update_id'] = $update_id;
        $data['panel_id'] = $panel_id;
        $this->view('picture_panel', $data);
    }

    /**
     * Render the panel's live region (picture state + actions).
     *
     * Called by the browser via MX (mx-get + X-* headers on the card
     * shell's unique-ID container) — never via Modules::run, so there is
     * no block_url() here. Session-authenticated like every admin page;
     * the X-* custom headers carry the panel context that would otherwise
     * come from route segments.
     *
     * The rendered view shows the real current state read live from the
     * database — never hard-coded or cached placeholder content.
     *
     * Responds with HTTP 400 (no view rendered) when required context is
     * missing or malformed, or when no uploader is configured for the
     * given module.
     *
     * @return void
     */
    public function render_panel_body(): void {
        $this->trongate_security->make_sure_allowed();

        $module = strtolower((string) ($_SERVER['HTTP_X_MODULE'] ?? ''));
        $update_id = isset($_SERVER['HTTP_X_UPDATE_ID']) ? (int) $_SERVER['HTTP_X_UPDATE_ID'] : 0;
        $panel_id = (string) ($_SERVER['HTTP_X_PANEL_ID'] ?? '');

        if (($module === '') || ($update_id === 0) || !$this->valid_panel_id($panel_id)) {
            http_response_code(400);
            return;
        }

        $settings = $this->model->get_uploader_settings($module);

        if ($settings === null) {
            http_response_code(400);
            return;
        }

        $data = $this->panel_data($settings, $module, $update_id, $panel_id);

        if ($data === []) {
            http_response_code(400);
            return;
        }

        $this->view('panel_body', $data);
    }

    // ============================================
    // Dedicated upload page + POST handler
    // ============================================

    /**
     * Dedicated upload page for one record.
     *
     * A full admin-template page for one record: shows the current picture
     * when one exists, the file input, and validation errors. The form is
     * a plain multipart POST to submit_upload() — deliberately NOT an MX
     * flow, so large file transfers are a normal browser submission.
     *
     * URL shape: image_uploader/upload_form/{module}/{update_id}
     *
     * @return void
     */
    public function upload_form(): void {
        $this->trongate_security->make_sure_allowed();

        $module = strtolower((string) segment(3));
        $update_id = (int) segment(4);

        $this->render_upload_form($module, $update_id);
    }

    /**
     * Handle the multipart upload POST.
     *
     * Validates the submitted file against the settings caps (required,
     * max_size, max_width, max_height — the same rule names the framework
     * documents for image uploads), runs the Image module's own layered
     * checks via $this->image->upload() (server-side MIME sniffing, GD
     * decode, downscale-to, optional thumbnail, and a stored extension that
     * follows the sniffed MIME rather than the client's file name), then
     * re-asserts the extension policy at this boundary via
     * enforce_file_extension() and stores the generated file name in the
     * target column.
     *
     * Replacing: when the record already had a picture, the old main +
     * thumb files are deleted only AFTER the new upload has fully
     * succeeded — a failed upload never destroys the existing picture.
     *
     * POST → redirect → GET back to the record's show page (where the
     * panel then reflects the new state). On validation failure the page
     * re-renders with errors inline.
     *
     * @return void
     */
    public function submit_upload(): void {
        $this->trongate_security->make_sure_allowed();

        $module = strtolower((string) post('module'));
        $update_id = (int) post('update_id');

        $settings = $this->model->get_uploader_settings($module);

        if (($settings === null) || ($update_id === 0)) {
            $this->not_configured();
            return;
        }

        $this->validation->set_rules('picture', 'picture', 'required|max_size[' . $settings['max_size'] . ']|max_width[' . $settings['max_width'] . ']|max_height[' . $settings['max_height'] . ']');

        if (!$this->validation->run()) {
            $this->render_upload_form($module, $update_id);
            return;
        }

        $old_file_name = $this->model->get_picture_file_name($module, $settings['column'], $update_id);

        if ($old_file_name === null) {
            redirect(BASE_URL . $module . '/manage');
            return;
        }

        // No-partial-state guarantee: remember exactly what is in the
        // record's folder before the upload starts, so a failure at ANY
        // later point can remove precisely what this attempt created —
        // never the record's existing picture, never another record's file.
        $folder = $this->record_folder($settings, $module, $update_id);
        $before = $this->folder_contents($folder);

        $config = [
            'destination' => $settings['destination'] . '/' . $update_id,
            'upload_to_module' => true,
            'target_module' => $module,
            'max_width' => $settings['resize_max_width'],
            'max_height' => $settings['resize_max_height'],
            'make_rand_name' => true
        ];

        if ($settings['thumbnails'] === true) {
            $config['thumbnail_dir'] = $settings['destination'] . '/' . $update_id . '/thumbs';
            $config['thumbnail_max_width'] = $settings['thumbnail_max_width'];
            $config['thumbnail_max_height'] = $settings['thumbnail_max_height'];
        }

        try {
            $file_info = $this->image->upload($config);
        } catch (Throwable $e) {
            $this->upload_failed($module, $update_id, $folder, $before, $this->public_message($e));
            return;
        }

        // Extension policy: the stored file must carry an extension that
        // matches the MIME type the server sniffed from its CONTENT — never
        // the extension the client put on the upload. A polyglot or
        // misnamed file is renamed to the canonical extension for its
        // sniffed type (main + thumbnail together) or the upload fails and
        // the files are removed.
        $enforced = $this->enforce_file_extension($file_info);
        $new_file_name = $enforced['name'];

        if ($new_file_name === '') {
            $this->upload_failed($module, $update_id, $folder, $before, $enforced['error']);
            return;
        }

        $this->model->set_picture_file_name($module, $settings['column'], $update_id, $new_file_name);

        // Replace: old files are removed only now that the new upload has
        // fully succeeded and been recorded.
        if (($old_file_name !== '') && ($old_file_name !== $new_file_name)) {
            $this->delete_recorded_files($settings, $module, $update_id, $old_file_name);
        }

        set_flashdata('The picture was successfully uploaded');
        redirect(BASE_URL . $module . '/show/' . $update_id);
    }

    // ============================================
    // Remove (MX POST, CSRF-gated)
    // ============================================

    /**
     * Remove the record's picture in place.
     *
     * Session-authenticated + CSRF-gated via validation->run(). The
     * remove button serializes (mx-vals): module, update_id, panel_id,
     * csrf_token. On success the recorded main + thumb files are deleted
     * (exact recorded names only — never a directory scan) and the column
     * is cleared to ''. The response is the re-rendered panel_body view:
     * MX's mx-target="none" + mx-select-oob on the button swap only the
     * panel's region, so the placeholder + Upload action appear without a
     * page reload.
     *
     * Business-rule failures respond with HTTP 422 and a JSON {ok, message}
     * body instead of a view. A CSRF failure is caught earlier, by
     * validation->run(), and exits through the framework's standard form
     * path (302 back to BASE_URL) — it never reaches the checks below.
     *
     * @return void
     */
    public function remove_picture(): void {
        $this->trongate_security->make_sure_allowed();
        $this->validation->run(); // CSRF gate

        $module = strtolower((string) post('module'));
        $update_id = (int) post('update_id');
        $panel_id = (string) post('panel_id');

        if (!$this->valid_panel_id($panel_id)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid panel identifier.']);
            return;
        }

        $settings = $this->model->get_uploader_settings($module);

        if (($settings === null) || ($update_id === 0)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid removal parameters.']);
            return;
        }

        $file_name = $this->model->get_picture_file_name($module, $settings['column'], $update_id);

        if ($file_name === null) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The record could not be found.']);
            return;
        }

        if ($file_name !== '') {
            $this->delete_recorded_files($settings, $module, $update_id, $file_name);
            $this->model->set_picture_file_name($module, $settings['column'], $update_id, '');
        }

        $data = $this->panel_data($settings, $module, $update_id, $panel_id);

        if ($data === []) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The panel could not be rendered.']);
            return;
        }

        $this->view('panel_body', $data);
    }

    // ============================================
    // Delete hook (Modules::run from the target module's delete flow)
    // ============================================

    /**
     * Delete the recorded picture files for a deleted record.
     *
     * Called via Modules::run from the injected delete hook in the target
     * module's submit_delete: the hook captures the file name from the
     * row BEFORE deletion and calls this method AFTER the row deletion has
     * succeeded — no orphans, no lost-file-with-surviving-row.
     *
     * Internal endpoint: public + block_url() + Modules::run only, so a
     * direct URL hit is rejected. No-op (silent) when the file name is
     * empty or the settings no longer exist.
     *
     * @param array $params Expected keys: module_name, update_id, file_name.
     * @return void
     */
    public function delete_files(array $params = []): void {
        block_url('image_uploader/delete_files');

        $module = strtolower((string) ($params['module_name'] ?? ''));
        $update_id = (int) ($params['update_id'] ?? 0);
        $file_name = (string) ($params['file_name'] ?? '');

        if (($module === '') || ($update_id === 0) || ($file_name === '')) {
            return;
        }

        $settings = $this->model->get_uploader_settings($module);

        if ($settings === null) {
            return;
        }

        $this->delete_recorded_files($settings, $module, $update_id, $file_name);
    }

    // ============================================
    // Internal helpers
    // ============================================

    /**
     * Assemble the view data for the panel's live region.
     *
     * Shared by render_panel_body() and remove_picture() so both render
     * identically from the same data-gathering logic. Returns [] when the
     * record no longer exists — callers treat an empty array as a failure
     * and respond with an error status rather than rendering a broken
     * view.
     *
     * @param array  $settings  The validated uploader settings.
     * @param string $module    The module whose record this is.
     * @param int    $update_id The record id.
     * @param string $panel_id  The panel's unique DOM id (already validated
     *                          by the caller via valid_panel_id()).
     * @return array The view data for the 'panel_body' view, or [] on failure.
     */
    private function panel_data(array $settings, string $module, int $update_id, string $panel_id): array {
        $file_name = $this->model->get_picture_file_name($module, $settings['column'], $update_id);

        if ($file_name === null) {
            return [];
        }

        // A recorded name that is not a plain, safe basename cannot belong
        // to a file this runtime wrote (and cannot be addressed safely in a
        // URL). Treat it as "no picture" rather than rendering a hostile or
        // broken path; the column is never rewritten here.
        if (!$this->is_safe_file_name($file_name)) {
            $file_name = '';
        }

        $data = [
            'view_module' => 'image_uploader',
            'module' => $module,
            'update_id' => $update_id,
            'panel_id' => $panel_id,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'file_name' => $file_name,
            'thumbnails' => $settings['thumbnails'],
            'upload_url' => BASE_URL . 'image_uploader/upload_form/' . $module . '/' . $update_id
        ];

        if ($file_name !== '') {
            $data['picture_url'] = $this->picture_url($settings, $module, $update_id, $file_name, false);
            $data['thumbnail_url'] = $this->picture_url($settings, $module, $update_id, $file_name, true);
        } else {
            $data['picture_url'] = '';
            $data['thumbnail_url'] = '';
        }

        return $data;
    }

    /**
     * Render the dedicated upload page for one record.
     *
     * Shared by upload_form() (URL endpoint) and submit_upload()'s
     * validation-failure path so the page re-renders with inline errors
     * and the current picture state intact.
     *
     * @param string $module    The module whose record this is.
     * @param int    $update_id The record id.
     * @return void
     */
    private function render_upload_form(string $module, int $update_id): void {
        $settings = $this->model->get_uploader_settings($module);

        if (($settings === null) || ($update_id === 0)) {
            $this->not_configured();
            return;
        }

        $current_file_name = $this->model->get_picture_file_name($module, $settings['column'], $update_id);

        if ($current_file_name === null) {
            redirect(BASE_URL . $module . '/manage');
            return;
        }

        if (!$this->is_safe_file_name($current_file_name)) {
            $current_file_name = '';
        }

        $data = $this->page_data($module, $update_id, 'Upload Picture');
        $data['current_file_name'] = $current_file_name;
        $data['current_picture_url'] = ($current_file_name === '') ? '' : $this->picture_url($settings, $module, $update_id, $current_file_name, false);
        $data['cancel_url'] = BASE_URL . $module . '/show/' . $update_id;
        $this->templates->admin($data);
    }

    /**
     * Assemble the view data for the dedicated upload page.
     *
     * @param string $module    The module whose record this is.
     * @param int    $update_id The record id.
     * @param string $headline  The page headline.
     * @return array The view data for the 'upload_form' view.
     */
    private function page_data(string $module, int $update_id, string $headline): array {
        return [
            'headline' => $headline,
            'module' => $module,
            'update_id' => $update_id,
            'form_location' => BASE_URL . 'image_uploader/submit_upload',
            'view_module' => 'image_uploader',
            'view_file' => 'upload_form'
        ];
    }

    /**
     * Build the public URL for a stored picture file.
     *
     * Module-owned files are served through the standard module-assets
     * trigger ({module}_module/...) with the settings destination as the
     * first path segment — no extra routing, no code outside the module.
     *
     * @param array  $settings   The validated uploader settings.
     * @param string $module     The module owning the file.
     * @param int    $update_id  The record id (folder scope).
     * @param string $file_name  The stored file name.
     * @param bool   $thumbnail  True for the thumbnail URL, false for main.
     * @return string The absolute public URL.
     */
    private function picture_url(array $settings, string $module, int $update_id, string $file_name, bool $thumbnail): string {
        $path = $settings['destination'] . '/' . $update_id;

        if ($thumbnail) {
            $path .= '/thumbs';
        }

        return BASE_URL . $module . '_module/' . $path . '/' . $file_name;
    }

    /**
     * Allowed stored extensions for each sniffed image MIME type.
     *
     * The first entry is the canonical extension used when the client's
     * file name does not already carry an allowed one. This table is the
     * single source of truth for the runtime's extension policy; nothing
     * here is derived from user input.
     */
    private const IMAGE_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/jpg'  => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/gif'  => ['gif'],
        'image/webp' => ['webp']
    ];

    /**
     * The per-record storage folder for a record.
     *
     * @param array  $settings  The validated uploader settings.
     * @param string $module    The module owning the files.
     * @param int    $update_id The record id.
     * @return string Absolute path (no trailing slash).
     */
    private function record_folder(array $settings, string $module, int $update_id): string {
        return APPPATH . 'modules/' . $module . '/' . $settings['destination'] . '/' . $update_id;
    }

    /**
     * List every file under a folder, as paths relative to that folder.
     *
     * @param string $folder The folder to scan.
     * @return array<int, string> Sorted relative file paths ([] when the
     *                            folder does not exist).
     */
    private function folder_contents(string $folder): array {
        if (!is_dir($folder)) {
            return [];
        }

        $found = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $found[] = substr($file->getPathname(), strlen($folder) + 1);
            }
        }

        sort($found);

        return $found;
    }

    /**
     * Remove everything an upload attempt added, then prune empty folders.
     *
     * Anything present before the attempt is left exactly as it was — the
     * record's existing picture survives a failed replace untouched.
     *
     * @param string $folder The per-record folder.
     * @param array  $before The folder listing captured before the attempt.
     * @return void
     */
    private function rollback_upload(string $folder, array $before): void {
        $after = $this->folder_contents($folder);

        foreach (array_diff($after, $before) as $relative) {
            $path = $folder . '/' . $relative;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->prune_empty_folders($folder);
    }

    /**
     * Remove empty subfolders under a folder, then the folder itself.
     *
     * rmdir() only ever removes an EMPTY directory, so a concurrent upload
     * into the same record can never be clobbered — the prune declines and
     * the folder stays.
     *
     * @param string $folder The folder to prune from.
     * @return void
     */
    private function prune_empty_folders(string $folder): void {
        if (!is_dir($folder)) {
            return;
        }

        $dirs = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $dirs[] = $file->getPathname();
            }
        }

        foreach ($dirs as $dir) {
            @rmdir($dir);
        }

        @rmdir($folder);
    }

    /**
     * Re-assert the extension policy on a completed upload.
     *
     * The framework Image module decides the stored extension from the MIME
     * type it sniffs from the file's CONTENT (see Image::upload()), so a
     * polyglot or misnamed upload cannot reach disk under an extension it
     * did not earn. This method is the runtime's own boundary check, kept as
     * defence in depth so the runtime cannot be loosened by a change — or a
     * regression — elsewhere: the sniffed MIME must be an accepted image
     * type, the stored name must be a safe bare basename, and the extension
     * must agree with that MIME. Anything else is renamed (main file +
     * thumbnail together) to the canonical extension, or refused — in which
     * case the caller removes what the attempt created.
     *
     * In normal operation the framework has already produced a conforming
     * name, so this returns it unchanged.
     *
     * @param array $file_info The array returned by image->upload().
     * @return array{name: string, error: string} The accepted (possibly
     *         renamed) file name and an error message on refusal.
     */
    private function enforce_file_extension(array $file_info): array {
        $mime = strtolower((string) ($file_info['file_type'] ?? ''));
        $allowed = self::IMAGE_EXTENSIONS[$mime] ?? null;

        if ($allowed === null) {
            return ['name' => '', 'error' => 'that file type is not a supported image'];
        }

        $file_name = basename((string) ($file_info['file_name'] ?? ''));

        if ($file_name === '') {
            return ['name' => '', 'error' => 'no file was produced'];
        }

        if (!$this->is_safe_file_name($file_name)) {
            return ['name' => '', 'error' => 'the uploaded file name was rejected'];
        }

        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($extension, $allowed, true)) {
            return ['name' => $file_name, 'error' => ''];
        }

        $canonical = $allowed[0];
        $stem = ($extension === '') ? rtrim($file_name, '.') : substr($file_name, 0, -(strlen($extension) + 1));
        $new_name = $stem . '.' . $canonical;

        if (($stem === '') || !$this->is_safe_file_name($new_name)) {
            return ['name' => '', 'error' => 'the uploaded file name was rejected'];
        }

        $moves = [[(string) ($file_info['file_path'] ?? ''), $new_name]];
        $thumbnail_path = (string) ($file_info['thumbnail_path'] ?? '');

        if ($thumbnail_path !== '') {
            $moves[] = [$thumbnail_path, $new_name];
        }

        foreach ($moves as $move) {
            $old_path = $move[0];
            $target_path = dirname($old_path) . '/' . $move[1];

            if (!is_file($old_path)) {
                return ['name' => '', 'error' => 'the uploaded file could not be secured'];
            }

            if ($target_path === $old_path) {
                continue;
            }

            if (is_file($target_path) || !@rename($old_path, $target_path)) {
                return ['name' => '', 'error' => 'the uploaded file could not be secured'];
            }
        }

        return ['name' => $new_name, 'error' => ''];
    }

    /**
     * Is this file name one the runtime is willing to store or serve?
     *
     * Bare basename only, a conservative charset, no dot-runs: a name that
     * passes can never escape its folder or smuggle a path.
     *
     * @param string $file_name The candidate file name.
     * @return bool True when the name is safe.
     */
    private function is_safe_file_name(string $file_name): bool {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,254}$/', $file_name) !== 1) {
            return false;
        }

        return (basename($file_name) === $file_name) && (strpos($file_name, '..') === false);
    }

    /**
     * Fail an upload attempt: undo its partial state, then redirect back.
     *
     * @param string $module    The module whose record was being uploaded to.
     * @param int    $update_id The record id.
     * @param string $folder    The per-record folder to restore.
     * @param array  $before    The folder listing from before the attempt.
     * @param string $message   The user-facing reason.
     * @return void
     */
    private function upload_failed(string $module, int $update_id, string $folder, array $before, string $message): void {
        $this->rollback_upload($folder, $before);
        set_flashdata('The picture could not be uploaded: ' . $message . '.');
        redirect('image_uploader/upload_form/' . $module . '/' . $update_id);
    }

    /**
     * Turn a caught exception into a message safe to show a user.
     *
     * Framework exceptions can quote absolute filesystem paths; those are
     * stripped, whitespace is collapsed and the result is length-capped so
     * no internal layout leaks into the flash message.
     *
     * @param Throwable $e The caught throwable.
     * @return string The user-facing message.
     */
    private function public_message(Throwable $e): string {
        $message = str_replace([APPPATH, BASE_URL], '', $e->getMessage());
        $message = trim((string) preg_replace('/\s+/', ' ', $message));

        if (strlen($message) > 200) {
            $message = substr($message, 0, 197) . '...';
        }

        return ($message === '') ? 'the upload failed' : $message;
    }

    /**
     * Delete the recorded main + thumb files for one record.
     *
     * Deletes ONLY the exact file names recorded in the database — the file
     * name is treated as a bare basename (no path components), so a hostile
     * or stale value can never escape the per-record folder. Missing files
     * are ignored silently (idempotent).
     *
     * Zero-residue: once both files are gone, the per-record folder (and its
     * thumbs/ subfolder) is pruned if — and only if — it is empty. Called
     * from the delete hook (row already gone) and from replace/remove, where
     * a later upload simply recreates the folder.
     *
     * @param array  $settings  The validated uploader settings.
     * @param string $module    The module owning the files.
     * @param int    $update_id The record id.
     * @param string $file_name The stored file name (basename only).
     * @return void
     */
    private function delete_recorded_files(array $settings, string $module, int $update_id, string $file_name): void {
        $file_name = basename($file_name);

        if (($file_name === '') || ($file_name === '.') || ($file_name === '..')) {
            return;
        }

        $base_dir = $this->record_folder($settings, $module, $update_id);

        $main_path = $base_dir . '/' . $file_name;

        if (is_file($main_path)) {
            @unlink($main_path);
        }

        $thumb_path = $base_dir . '/thumbs/' . $file_name;

        if (is_file($thumb_path)) {
            @unlink($thumb_path);
        }

        // Zero-residue: the per-record folder exists only to hold this
        // record's picture, so once both files are gone it has no purpose.
        $this->prune_empty_folders($base_dir);
    }

    /**
     * Validate a panel identifier.
     *
     * @param string $panel_id The panel id from the request.
     * @return bool True when the id looks like one of ours.
     */
    private function valid_panel_id(string $panel_id): bool {
        return (preg_match('/^[A-Za-z0-9]{8,32}$/', $panel_id) === 1);
    }

    /**
     * Handle a request for a module with no configured uploader.
     *
     * @return void
     */
    private function not_configured(): void {
        http_response_code(404);
        echo 'No image uploader is configured for that module.';
    }
}
