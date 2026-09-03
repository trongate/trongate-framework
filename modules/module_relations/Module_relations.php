<?php
/**
 * Module_relations Controller — RUNTIME for generated module relations.
 *
 * Generated application modules reach this controller via one of two paths:
 *
 *   1. Page load / Modules::run() — draw_summary_panel(), fetch_create_options(),
 *      sync_create_claim(), release_create_link() are called directly from a
 *      generated module's controller (show page, create/edit form, delete hook).
 *   2. Browser / MX (mx-get, mx-post) — render_panel_body(), submit_association(),
 *      remove_association() are hit directly by the browser against the
 *      summary panel rendered by draw_summary_panel(). These are the only
 *      entry points reachable by direct HTTP request; they are therefore
 *      session-authenticated (trongate_security) and, for the two mutating
 *      endpoints, CSRF-gated (validation->run()).
 *
 * Generation-time logic (choosing modules, relation type, schema SQL,
 * writing the relation settings JSON) is NOT part of this file. That work
 * is done once, by Flo's wizard, in
 * modules/trongate_control/module_relations_builder/. This controller only
 * reads the settings JSON that the wizard already wrote
 * (via Module_relations_model::get_relation_settings()) and operates the
 * relationship it describes — displaying associated records, adding and
 * removing associations, populating relationship dropdowns on generated
 * forms, and maintaining one-to-one integrity (no orphaned partners).
 *
 * Surface:
 *   draw_summary_panel   — renders the show-page "Associated …" panel shell
 *                           (Modules::run, page load).
 *   render_panel_body     — renders the panel's live content: the associate
 *                           form and the list of associated records
 *                           (browser, MX mx-get + X-* headers).
 *   submit_association    — creates an association from the panel's add
 *                           form (browser, MX POST, CSRF-gated).
 *   remove_association    — removes an association from a panel remove
 *                           button (browser, MX POST, CSRF-gated).
 *   fetch_create_options  — dropdown options for a generated create/edit
 *                           form (Modules::run, internal only).
 *   sync_create_claim     — one-to-one claim/release triggered by a
 *                           generated create/edit form (Modules::run,
 *                           internal only).
 *   release_create_link   — one-to-one partner release triggered by a
 *                           generated module's delete hook (Modules::run,
 *                           internal only).
 *   release_children      — one-to-many children release triggered by a
 *                           generated parent module's delete hook
 *                           (Modules::run, internal only).
 *   release_junction_links — junction-row cleanup triggered by a generated
 *                           module's delete hook for junction relations
 *                           (1:1 bridge / many to many) (Modules::run,
 *                           internal only).
 */
class Module_relations extends Trongate {

    /**
     * Constructor.
     *
     * @param string|null $module_name The module name (set by the framework).
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
    }

    /**
     * Render the "Associated …" summary panel shell on a generated show page.
     *
     * Called via Modules::run() from a generated module's show-page
     * controller, passing the OTHER module in the relation as
     * $alt_module_name. Renders nothing (no output, no error) when no
     * relation is configured for this module pair, or when there is no
     * record id in the URL to scope the panel to — a show page for a
     * module with no configured relation should look exactly as if this
     * call was never made.
     *
     * The panel itself is a shell: it seeds a CSRF token (if one doesn't
     * already exist in the session) for the panel's later MX POSTs, and
     * hands the view enough context (calling module, alt module, update id,
     * a fresh unique panel id) for the browser to fetch the live content
     * via render_panel_body().
     *
     * @param string|null $alt_module_name The other module in the relation
     *                                     (the module whose records this
     *                                     panel will show/manage).
     * @return void
     */
    public function draw_summary_panel(?string $alt_module_name = null): void {
        block_url('module_relations/draw_summary_panel');

        $calling_module = strtolower((string) segment(1));
        $update_id = (int) segment(3);
        $alt_module_name = strtolower($alt_module_name);

        $settings = $this->model->get_relation_settings($calling_module, $alt_module_name);

        if (($settings === null) || ($update_id === 0)) {
            return; // No relation configured — render nothing.
        }

        $associated = $this->model->est_associated_module($settings, $calling_module);

        if ($associated === null) {
            return;
        }

        $data['view_module'] = 'module_relations';
        $data['calling_module'] = $calling_module;
        $data['alt_module'] = $alt_module_name;
        $data['update_id'] = $update_id;
        $data['relation_name'] = 'associated_' . $settings[0]['module_name'] . '_and_' . $settings[1]['module_name'];
        $data['associated_singular'] = $associated['record_name_singular'];
        $data['associated_plural'] = $associated['record_name_plural'];
        $data['summary_panel_id'] = make_rand_str(16);

        // Seed the CSRF token when no page form has created one yet — the
        // panel's MX POSTs (submit/remove) send it via mx-vals and the
        // endpoints gate on validation->run().
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $data['csrf_token'] = $_SESSION['csrf_token'];

        $relationship_type = ($settings[2]['relationship_type']) ?? null;

        if ($relationship_type === null) {
            return;
        }

        $noun = ($relationship_type === 'one to one') ? $data['associated_singular'] : $data['associated_plural'];
        $data['card_heading'] = 'Associated ' . out(ucwords(str_replace('_', ' ', $noun)));
        $this->view('summary_panel', $data);
    }

    /**
     * Render the panel body (associate form + associated items) for a
     * summary panel.
     *
     * Called by the browser via MX (mx-get + mx-headers on the summary
     * panel's unique-ID container) — never via Modules::run, so there is
     * no block_url() here. Session-authenticated like every admin page;
     * the X-* custom headers carry the panel context that would otherwise
     * come from route segments.
     *
     * The rendered view shows real associated records and real available
     * options read live from the database — never hard-coded or cached
     * placeholder content.
     *
     * Responds with HTTP 400 (no view rendered) when required context is
     * missing or malformed, or when no relation is configured for the
     * given module pair.
     *
     * @return void
     */
    public function render_panel_body() {
        $this->trongate_security->make_sure_allowed();

        $calling_module = strtolower((string) ($_SERVER['HTTP_X_CALLING_MODULE'] ?? ''));
        $alt_module = strtolower((string) ($_SERVER['HTTP_X_ALT_MODULE'] ?? ''));
        $update_id = isset($_SERVER['HTTP_X_UPDATE_ID']) ? (int) $_SERVER['HTTP_X_UPDATE_ID'] : 0;
        $panel_id = (string) ($_SERVER['HTTP_X_PANEL_ID'] ?? '');

        if (($calling_module === '') || ($alt_module === '') || ($update_id === 0) || !$this->valid_panel_id($panel_id)) {
            http_response_code(400);
            return;
        }

        $settings = $this->model->get_relation_settings($calling_module, $alt_module);
        if ($settings === null) {
            http_response_code(400);
            return;
        }

        $data = $this->panel_data($settings, $calling_module, $update_id, $panel_id);
        if ($data === []) {
            http_response_code(400);
            return;
        }

        $this->view('panel_body', $data);
    }

    /**
     * Create an association (MX POST from the panel's add form).
     *
     * Session-authenticated + CSRF-gated via validation->run(). The form
     * serializes (mx-vals + fields): calling_module, alt_module, update_id,
     * panel_id, value, csrf_token.
     *
     * On success the response is the panel_body view again — MX's
     * mx-target="none" + mx-select-oob on the form swap only the two panel
     * regions (associate form + associated-items list) into place, instead
     * of re-rendering the whole panel. On any validation or business-rule
     * failure, responds with HTTP 422 and a JSON {ok, message} body instead
     * of a view.
     *
     * @return void
     */
    public function submit_association(): void {
        $this->trongate_security->make_sure_allowed();
        $this->validation->run(); // CSRF gate

        $calling_module = strtolower((string) post('calling_module'));
        $alt_module = strtolower((string) post('alt_module'));
        $update_id = (int) post('update_id');
        $value = (int) post('value');
        $panel_id = (string) post('panel_id');

        if (!$this->valid_panel_id($panel_id)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid panel identifier.']);
            return;
        }

        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($update_id === 0) || ($value === 0)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid association parameters.']);
            return;
        }

        if (!$this->model->submit_association($settings, $calling_module, $update_id, $value)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The association could not be created.']);
            return;
        }

        $data = $this->panel_data($settings, $calling_module, $update_id, $panel_id);
        if ($data === []) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The association panel could not be rendered.']);
            return;
        }

        $this->view('panel_body', $data);
    }

    /**
     * Remove an association (MX POST from a remove button).
     *
     * Same security contract as submit_association() (session auth + CSRF).
     * `value` is the id returned by fetch_associated_rows() for the row
     * being removed — the junction row id for junction-backed relations
     * (one-to-one with bridge, many-to-many), or the associated record's
     * own id for direct (non-junction) relations.
     *
     * On success the response is the panel_body view again — MX's
     * mx-target="none" + mx-select-oob on the button swap only the two
     * panel regions into place. When a removal frees up an option, the
     * associate-form region re-renders with the form visible; when it
     * leaves nothing available, the form stays hidden. On any validation
     * or business-rule failure, responds with HTTP 422 and a JSON
     * {ok, message} body instead of a view.
     *
     * @return void
     */
    public function remove_association(): void {
        $this->trongate_security->make_sure_allowed();
        $this->validation->run(); // CSRF gate

        $calling_module = strtolower((string) post('calling_module'));
        $alt_module = strtolower((string) post('alt_module'));
        $update_id = (int) post('update_id');
        $value = (int) post('value');
        $panel_id = (string) post('panel_id');

        if (!$this->valid_panel_id($panel_id)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid panel identifier.']);
            return;
        }

        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($update_id === 0) || ($value === 0)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Invalid association parameters.']);
            return;
        }

        if (!$this->model->disassociate_association($settings, $calling_module, $update_id, $value)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The association could not be removed.']);
            return;
        }

        $data = $this->panel_data($settings, $calling_module, $update_id, $panel_id);
        if ($data === []) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'The association panel could not be rendered.']);
            return;
        }

        $this->view('panel_body', $data);
    }

    // ============================================
    // Create-form runtime endpoints (create-form injection)
    // ============================================

    /**
     * Dropdown options for a generated create/edit form.
     *
     * Called from a generated controller's get_{alt_singular}_options()
     * method via Modules::run — this is an internal endpoint: public +
     * block_url() + Modules::run only, so a direct URL hit is rejected
     * (403) by the framework before this method body runs.
     *
     * Returns the form_dropdown() [key => value] contract. Includes a
     * leading '— None —' option so records can be created unlinked and
     * existing links can be cleared from an edit form: the generated
     * controllers map a cleared pick to NULL, and sync_create_claim()
     * treats a value of 0 as "release only, don't claim."
     *
     * @param array $params {
     *     @type string $calling_module The module whose create/edit form this is.
     *     @type string $alt_module     The other module in the relation.
     *     @type int    $selected_key   The currently-linked record id (edit forms only; 0 on create).
     * }
     * @return array<int|string,string> Dropdown options, or [] when no relation is configured.
     */
    public function fetch_create_options(array $params = []): array {
        block_url('module_relations/fetch_create_options');

        $calling_module = strtolower((string) ($params['calling_module'] ?? ''));
        $alt_module = strtolower((string) ($params['alt_module'] ?? ''));
        $selected_key = (int) ($params['selected_key'] ?? 0);
        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($calling_module === '')) {
            return [];
        }

        return $this->model->fetch_create_options($settings, $calling_module, $selected_key);
    }

    /**
     * Bidirectional one-to-one claim/release from a generated create/edit form.
     *
     * Called from a generated controller's sync_with_{alt_singular}()
     * method via Modules::run, after the calling record's own INSERT/UPDATE
     * has already been written — internal endpoint (public + block_url() +
     * Modules::run only). No-op for relation types other than direct
     * (non-bridged) one-to-one.
     *
     * Enforces the same no-orphan guarantee as the panel's submit path: any
     * previous partner (on either side) is released before the new pair is
     * claimed. This is the single source of truth for that logic — generated
     * modules never duplicate this SQL themselves.
     *
     * @param array $params {
     *     @type string $calling_module The module being created/edited.
     *     @type string $alt_module     The other module in the relation.
     *     @type int    $update_id      The calling record's id.
     *     @type int    $value          The alt record id being claimed (0 = clear the link).
     *     @type int    $prev_value     The alt id that was linked before this edit (0 on create).
     * }
     * @return void
     */
    public function sync_create_claim(array $params = []): void {
        block_url('module_relations/sync_create_claim');

        $calling_module = strtolower((string) ($params['calling_module'] ?? ''));
        $alt_module = strtolower((string) ($params['alt_module'] ?? ''));
        $update_id = (int) ($params['update_id'] ?? 0);
        $value = (int) ($params['value'] ?? 0);
        $prev_value = (int) ($params['prev_value'] ?? 0);
        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($calling_module === '')) {
            return;
        }

        $this->model->sync_create_claim($settings, $calling_module, $update_id, $value, $prev_value);
    }

    /**
     * Release a one-to-one link when a generated record is deleted (delete hook).
     *
     * Called from a generated controller's release_{alt_singular}_link()
     * method via Modules::run, before the calling record's row is deleted —
     * internal endpoint (public + block_url() + Modules::run only). No-op
     * for relation types other than direct (non-bridged) one-to-one.
     *
     * Zeroes the partner's back-FK so it never ends up pointing at a
     * deleted record. The deleted record's own FK is not touched here — it
     * is removed along with the row itself.
     *
     * @param array $params {
     *     @type string $calling_module The module whose record is being deleted.
     *     @type string $alt_module     The other module in the relation.
     *     @type int    $update_id      The record id being deleted.
     * }
     * @return void
     */
    public function release_create_link(array $params = []): void {
        block_url('module_relations/release_create_link');

        $calling_module = strtolower((string) ($params['calling_module'] ?? ''));
        $alt_module = strtolower((string) ($params['alt_module'] ?? ''));
        $update_id = (int) ($params['update_id'] ?? 0);
        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($calling_module === '')) {
            return;
        }

        $this->model->release_create_link($settings, $calling_module, $update_id);
    }

    /**
     * Release a one-to-many parent's children when the parent is deleted
     * (delete hook).
     *
     * Called from a generated PARENT controller's release_{children}_links()
     * method via Modules::run, before the parent record's row is deleted —
     * internal endpoint (public + block_url() + Modules::run only). No-op
     * for relation types other than one to many, and for child-side calls.
     *
     * NULLs the child table's FK wherever it points at the deleted record,
     * so no child is ever left pointing at a deleted parent.
     *
     * @param array $params {
     *     @type string $calling_module The module whose record is being deleted.
     *     @type string $alt_module     The other module in the relation.
     *     @type int    $update_id      The record id being deleted.
     * }
     * @return void
     */
    public function release_children(array $params = []): void {
        block_url('module_relations/release_children');

        $calling_module = strtolower((string) ($params['calling_module'] ?? ''));
        $alt_module = strtolower((string) ($params['alt_module'] ?? ''));
        $update_id = (int) ($params['update_id'] ?? 0);
        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($calling_module === '')) {
            return;
        }

        $this->model->release_children($settings, $calling_module, $update_id);
    }

    /**
     * Release a junction relation's rows when either side's record is
     * deleted (delete hook).
     *
     * Called from a generated controller's release_junction_links() method
     * via Modules::run, before the record's row is deleted — internal
     * endpoint (public + block_url() + Modules::run only). No-op for
     * relation types without a junction table (direct one to one, one to
     * many), when the calling module isn't part of the relation, and for
     * invalid record ids.
     *
     * Deletes every junction row referencing the record on EITHER side (a
     * many-to-many link is symmetric — the record may appear in the
     * junction's first or second FK column), so no junction row is ever
     * left pointing at a deleted record.
     *
     * @param array $params {
     *     @type string $calling_module The module whose record is being deleted.
     *     @type string $alt_module     The other module in the relation.
     *     @type int    $update_id      The record id being deleted.
     * }
     * @return void
     */
    public function release_junction_links(array $params = []): void {
        block_url('module_relations/release_junction_links');

        $calling_module = strtolower((string) ($params['calling_module'] ?? ''));
        $alt_module = strtolower((string) ($params['alt_module'] ?? ''));
        $update_id = (int) ($params['update_id'] ?? 0);
        $settings = $this->model->get_relation_settings($calling_module, $alt_module);

        if (($settings === null) || ($calling_module === '')) {
            return;
        }

        $this->model->release_junction_links($settings, $calling_module, $update_id);
    }

    // ============================================
    // Internal helpers
    // ============================================

    /**
     * Assemble the view data for a panel's live content (used by
     * render_panel_body(), submit_association(), and remove_association()
     * so all three render identically from the same data-gathering logic).
     *
     * Returns [] when the calling module isn't part of the given relation,
     * or when the relation's "other side" can't be resolved — callers treat
     * an empty array as a failure and respond with an error status rather
     * than rendering a broken view.
     *
     * For a direct (non-bridged) one-to-one relation, the associate form is
     * hidden once an association already exists: the calling record can
     * hold at most one partner, so replacing it is remove-then-add rather
     * than picking a second option alongside the first. For every relation
     * type, the form is also hidden when there are simply no available
     * options left to pick from.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose panel this is.
     * @param int    $update_id      The calling record's id.
     * @param string $panel_id       The panel's unique DOM id (already
     *                                validated by the caller via valid_panel_id()).
     * @return array The view data for the 'panel_body' view, or [] on failure.
     */
    private function panel_data(array $settings, string $calling_module, int $update_id, string $panel_id): array {
        $associated = $this->model->est_associated_module($settings, $calling_module);
        if ($associated === null) {
            return [];
        }

        $associated_rows = $this->model->fetch_associated_rows($settings, $calling_module, $update_id);
        $available_options = $this->model->fetch_available_options($settings, $calling_module, $update_id);

        $form_visible = (count($available_options) > 0);
        $relationship_type = $settings[2]['relationship_type'] ?? '';
        if ($relationship_type === 'one to one') {
            $form_visible = $form_visible && (count($associated_rows) === 0);
        }

        // Singular name of the module whose show page hosts this panel
        // (settings[0] is module_a; the caller may be either side). May
        // be empty on hand-edited/legacy settings; the view then falls
        // back to a generic statement that needs no record names.
        $calling_singular = ($settings[0]['module_name'] === $calling_module)
            ? ($settings[0]['record_name_singular'] ?? '')
            : ($settings[1]['record_name_singular'] ?? '');

        return [
            'view_module'         => 'module_relations',
            'calling_module'      => $calling_module,
            'alt_module'          => $associated['module_name'],
            'update_id'           => $update_id,
            'relation_name'       => 'associated_' . $settings[0]['module_name'] . '_and_' . $settings[1]['module_name'],
            'associated_singular' => $associated['record_name_singular'] ?? '',
            'associated_plural'   => $associated['record_name_plural'] ?? '',
            'calling_singular'    => $calling_singular,
            'panel_id'            => $panel_id,
            // The unique-ID container is the panel's DOM anchor
            // (make_rand_str() may start with a digit — escape it for CSS
            // selectors, e.g. `#\37 bgZaem6c6ExxnN2`). Never reference
            // .associated-items alone; it isn't scoped to a single panel.
            'panel_selector'      => $this->css_escape_id($panel_id),
            // Fresh session token — the fragments' mx-vals must stay in sync
            // with the session or the next submit/remove fails CSRF.
            'csrf_token'          => $_SESSION['csrf_token'] ?? '',
            'associated_rows'     => $associated_rows,
            'available_options'   => $available_options,
            'form_visible'        => $form_visible
        ];
    }

    /**
     * Whether a posted panel id is safe to interpolate into DOM ids.
     *
     * Panel ids are generated by make_rand_str() (alphanumeric only) and
     * are interpolated into id/selector strings on both the initially
     * rendered page and every MX swap response — this is a strict
     * whitelist check, not an escaping routine, because the value is used
     * to build both HTML attribute values and a CSS selector.
     *
     * @param string $panel_id The posted panel id.
     * @return bool True when the id matches the expected format.
     */
    private function valid_panel_id(string $panel_id): bool {
        return (preg_match('/^[A-Za-z0-9]{8,32}$/', $panel_id) === 1);
    }

    /**
     * Escape an element id for safe use inside a CSS ID selector.
     *
     * make_rand_str() can emit ids that start with a digit, and a raw
     * `#7abc…` selector is invalid CSS (an id selector cannot start with an
     * unescaped digit). A leading digit is rewritten as a CSS hex escape,
     * exactly as the CSS spec requires: `7bgZaem…` becomes `\37 bgZaem…`.
     *
     * @param string $id The element id (expected to already be
     *                    valid_panel_id()-checked, but this method is
     *                    defensive regardless).
     * @return string The id, escaped for safe use in a CSS selector.
     */
    private function css_escape_id(string $id): string {
        if ($id === '') {
            return '';
        }
        if (ctype_digit($id[0])) {
            return '\\' . dechex(ord($id[0])) . ' ' . substr($id, 1);
        }
        return $id;
    }

}