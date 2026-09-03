<?php
/**
 * Module_relations_model — Data layer for the module relations RUNTIME.
 *
 * Serves three runtime needs for relations already created by Flo's wizard:
 *
 *   - the show-page "Associated …" panel (list current associations, list
 *     available options, create/remove an association);
 *   - dropdown options and one-to-one claim/release for a generated
 *     module's create/edit form;
 *   - one-to-one partner release on a generated module's delete hook.
 *
 * Generation-time logic — choosing eligible modules, picking a relation
 * type, generating schema SQL, and writing the relation settings JSON —
 * is NOT part of this file. That work happens once, at generation time, in
 * modules/trongate_control/module_relations_builder/Module_relations_builder_model.php.
 * This model only READS the settings JSON that the builder already wrote
 * (see get_relation_settings()) and operates the relationship it describes.
 *
 * Relation settings shape (as decoded from the JSON file):
 *   $settings[0] — module "A" entry: {module_name, record_name_singular,
 *                  record_name_plural, identifier_column}
 *   $settings[1] — module "B" entry: same shape as $settings[0]
 *   $settings[2] — relation config: {relationship_type, bridging_table}
 *   relationship_type is one of 'one to many' | 'one to one' | 'many to many'.
 *   bridging_table is only meaningful for 'one to one' — true means the
 *   relation is backed by a junction table rather than mirrored FK columns.
 *
 * Security / trust boundary — identifiers in raw SQL:
 *   Every method below builds SQL by concatenating table and column names
 *   (module_name, record_name_singular . '_id', junction table names)
 *   directly into query strings, rather than binding them as parameters —
 *   MySQL has no bind-parameter syntax for identifiers, so this is the only
 *   way to build these queries at all. This is safe ONLY because those
 *   values are not attacker-controlled at the point they reach this file:
 *   they come from the settings JSON, which is written exclusively by the
 *   builder after it has already validated the underlying table/column
 *   names against a strict `^[a-z0-9_]+$` pattern. This model does not
 *   re-validate module_name or record_name_singular before use — it trusts
 *   the settings file as a whole. The one exception is identifier_column,
 *   which IS re-validated here (see valid_identifier_columns()), because it
 *   is treated as more speculative/free-form input. If the settings JSON
 *   format or its write path ever changes, this trust boundary needs to be
 *   re-examined.
 */
class Module_relations_model extends Model {

    /**
     * Path to the runtime module's settings directory, relative to APPPATH.
     *
     * Settings live inside the app's own module_relations runtime module
     * (not inside trongate_control) so they are versioned alongside the
     * generated application code, and survive independently of the builder.
     */
    private const SETTINGS_DIR = 'modules/module_relations/settings';

    // ============================================
    // Introspection helpers
    // ============================================

    /**
     * List the real column names of a table, straight from the database.
     *
     * Used as the whitelist that identifier_column values are checked
     * against (see valid_identifier_columns()) — this is what keeps a
     * malformed or stale identifier_column entry in the settings JSON from
     * ever reaching a query.
     *
     * @param string $table Table name. Must already match `^[a-z0-9_]+$`;
     *                      anything else returns [] rather than querying.
     * @return array<string> The table's column names, or [] when the table
     *                       name is empty/malformed.
     */
    public function get_table_columns(string $table): array {
        $columns = [];
        if ($table === '' || !preg_match('/^[a-z0-9_]+$/', $table)) {
            return $columns;
        }
        $rows = $this->db->query('SHOW COLUMNS FROM `' . $table . '`', 'object');
        foreach ($rows as $row) {
            $columns[] = $row->Field ?? '';
        }
        return array_values(array_filter($columns));
    }

    // ============================================
    // Create-form runtime data (create-form injection)
    // ============================================

    /**
     * Dropdown options for a generated create/edit form's relation field.
     *
     * Behaviour depends on the relation type:
     *
     *   - one to many, on the child's create/edit form: every parent
     *     record is offered, since a child may point at any parent. A
     *     leading '— None —' (key 0) option is always included so the
     *     dropdown never forces a pick (a child may stay unlinked).
     *   - one to many, on the PARENT's create/edit form: [] — a parent
     *     form never gets a child dropdown; children are attached and
     *     detached from the show-page panel instead.
     *   - one to one, no bridging table: only currently-unclaimed alt
     *     records are offered, plus — on an edit form — whichever record
     *     is already linked (so editing never silently drops the existing
     *     partner from the list). '— None —' (key 0) again means "no
     *     partner"; sync_create_claim() treats a submitted value of 0 as
     *     "release the existing link, don't claim a new one."
     *   - junction-backed relations (one to one with a bridge, or many to
     *     many): returns [] deliberately. These relation types have no
     *     create-form dropdown by design — the show-page panel is the only
     *     surface for managing them.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose create/edit form this is.
     * @param int    $selected_key   The record id currently linked, when
     *                               editing (0 when creating, or when there
     *                               is no existing link).
     * @return array<int|string,string> Options in the PHP form_dropdown()
     *                                  [key => value] contract, or [] when
     *                                  $calling_module isn't part of the
     *                                  relation or the relation type has no
     *                                  create-form dropdown.
     */
    public function fetch_create_options(array $settings, string $calling_module, int $selected_key = 0): array {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $relationship_type = $settings[2]['relationship_type'] ?? '';

        if (($calling_module !== $module_a) && ($calling_module !== $module_b)) {
            return [];
        }

        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return [];
        }

        if ($relationship_type === 'one to many') {
            if ($calling_module === $module_a) {
                return []; // Parent view: no create-form child dropdown —
                           // children are managed from the show-page panel.
            }
            $sql = 'SELECT id, ' . $this->identifier_expr($alt, $alt['module_name']) . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '`'
                 . ' ORDER BY ' . $this->identifier_order($alt, $alt['module_name']);
            return [0 => '— None —'] + $this->options_to_dropdown($this->db->query_bind($sql, [], 'array'));
        }

        if ($this->is_direct_one_to_one($settings)) {
            $calling_entry = $this->get_settings_for_module($settings, $calling_module);
            $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id';
            $sql = 'SELECT id, ' . $this->identifier_expr($alt, $alt['module_name']) . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '`'
                 . ' WHERE (`' . $back_fk . '` IS NULL OR `' . $back_fk . '` = 0)';
            $params = [];
            if ($selected_key > 0) {
                // Include the currently-linked record even though its
                // back-FK is claimed (not NULL, not 0) — otherwise editing
                // would make the existing partner disappear from its own
                // dropdown.
                $sql .= ' OR id = :selected_key';
                $params['selected_key'] = $selected_key;
            }
            $sql .= ' ORDER BY ' . $this->identifier_order($alt, $alt['module_name']);
            return [0 => '— None —'] + $this->options_to_dropdown($this->db->query_bind($sql, $params, 'array'));
        }

        return []; // Junction-backed relation types — no create-form dropdown, by design.
    }

    /**
     * Bidirectional one-to-one claim/release, called from a generated
     * create/edit form after the calling record's own row has been written.
     *
     * No-op for any relation type other than direct (non-bridged) one to
     * one, and a no-op when $update_id is invalid.
     *
     * This method runs AFTER the calling controller's INSERT/UPDATE, so on
     * the edit path the calling record's own FK column has already been
     * overwritten by the time this runs — which is why the pre-edit partner
     * must be passed in separately as $prev_value (the controller is
     * expected to have read it before its own UPDATE ran). The steps:
     *
     *   1. Edit path only: if the partner changed, release the PREVIOUS
     *      partner's back-FK. (The calling record's own FK was already
     *      overwritten by the controller's UPDATE, so nothing needs
     *      clearing on that side here.)
     *   2. If $value is a real record (not a clear): release that record
     *      from whichever OTHER calling record currently claims it, so the
     *      new claim never creates two owners for one partner. This read
     *      happens before any write, since the write in step 3 would
     *      otherwise make the "previous owner" unrecoverable.
     *   3. Claim both sides of the link. Re-writing the calling record's
     *      own FK here is a deliberate no-op/self-heal: it is normally
     *      already correct (the controller wrote it), but re-asserting it
     *      costs nothing and protects against it ever having drifted.
     *
     * A cleared FK ($value < 1) stops after step 1 — releasing the previous
     * partner is all that should happen; editing a record to remove its
     * one-to-one partner must not touch anything else.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module being created/edited.
     * @param int    $update_id      The calling record's id.
     * @param int    $value          The alt record id being claimed
     *                               (0 = clear the link entirely).
     * @param int    $prev_value     The alt id that was linked before this
     *                               edit (0 on create, or when there was no
     *                               previous link).
     * @return void
     */
    public function sync_create_claim(array $settings, string $calling_module, int $update_id, int $value, int $prev_value = 0): void {
        if ($update_id < 1) {
            return;
        }
        if (!$this->is_direct_one_to_one($settings)) {
            return;
        }

        $calling_entry = $this->get_settings_for_module($settings, $calling_module);
        $alt = $this->est_associated_module($settings, $calling_module);
        $alt_fk = $alt['record_name_singular'] . '_id';            // column on the calling table
        $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id'; // column on the alt table

        // 1. Edit path: release the previous partner's back-FK, but only
        //    if we're actually changing partners (skip when re-saving the
        //    same value) and only if that back-FK still points at us —
        //    guards against clobbering a link that changed via another
        //    path since the form was loaded.
        if (($prev_value > 0) && ($prev_value !== $value)) {
            $this->db->query_bind(
                'UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = 0'
                . ' WHERE id = :prev_value AND `' . $back_fk . '` = :update_id',
                ['prev_value' => $prev_value, 'update_id' => $update_id]
            );
        }

        if ($value < 1) {
            return; // FK cleared — release only, nothing further to claim.
        }

        // 2. Release the chosen alt record from any previous owner other
        //    than us — read before any write, since step 3 would otherwise
        //    overwrite the value we need to read here.
        $owner = $this->db->query_bind(
            'SELECT `' . $back_fk . '` AS owner_id FROM `' . $alt['module_name'] . '` WHERE id = :value',
            ['value' => $value],
            'array'
        );
        $prev_owner_id = (int) ($owner[0]['owner_id'] ?? 0);
        if (($prev_owner_id > 0) && ($prev_owner_id !== $update_id)) {
            $this->db->query_bind(
                'UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = 0 WHERE id = :prev_owner_id',
                ['prev_owner_id' => $prev_owner_id]
            );
        }

        // 3. Claim both sides.
        $this->db->query_bind(
            'UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = :value WHERE id = :update_id',
            ['update_id' => $update_id, 'value' => $value]
        );
        $this->db->query_bind(
            'UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = :update_id WHERE id = :value',
            ['update_id' => $update_id, 'value' => $value]
        );
    }

    /**
     * Release a direct (non-bridged) one-to-one link when a record is
     * deleted — intended to be called from a generated module's delete
     * hook, BEFORE the row is actually removed.
     *
     * No-op for any relation type other than direct one to one, and a
     * no-op when $update_id is invalid.
     *
     * Only the OTHER side's back-FK is touched here (set to 0) so the
     * surviving partner is never left pointing at a row that's about to
     * disappear. The deleted record's own FK needs no explicit cleanup —
     * it is removed along with the row itself.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose record is being deleted.
     * @param int    $update_id      The record id about to be deleted.
     * @return void
     */
    public function release_create_link(array $settings, string $calling_module, int $update_id): void {
        if ($update_id < 1) {
            return;
        }
        if (!$this->is_direct_one_to_one($settings)) {
            return;
        }

        $calling_entry = $this->get_settings_for_module($settings, $calling_module);
        $alt = $this->est_associated_module($settings, $calling_module);
        $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id';
        $this->db->query_bind(
            'UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = 0'
            . ' WHERE `' . $back_fk . '` = :update_id',
            ['update_id' => $update_id]
        );
    }

    /**
     * Release every child record when a one-to-many PARENT record is
     * deleted — intended to be called from the parent module's delete
     * hook, BEFORE the row is actually removed.
     *
     * No-op for any relation type other than one to many, a no-op when
     * $update_id is invalid, and a no-op when the calling module is the
     * CHILD side of the relation (a deleted child has no dependants — its
     * own FK goes with the row; nothing references it).
     *
     * Zeros the child table's FK wherever it points at the deleted parent,
     * so children are never left pointing at a ghost record — the same
     * no-orphan guarantee the 1:1 delete hook provides, inverted (one
     * parent → many children instead of one partner).
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose record is being deleted.
     * @param int    $update_id      The record id about to be deleted.
     * @return void
     */
    public function release_children(array $settings, string $calling_module, int $update_id): void {
        if ($update_id < 1) {
            return;
        }
        if (($settings[2]['relationship_type'] ?? '') !== 'one to many') {
            return;
        }

        $parent = $settings[0]['module_name'] ?? '';
        if ($calling_module !== $parent) {
            return; // Child-side delete: nothing references the child.
        }

        $child = $settings[1]['module_name'] ?? '';
        $fk = ($settings[0]['record_name_singular'] ?? '') . '_id';
        $this->db->query_bind(
            'UPDATE `' . $child . '` SET `' . $fk . '` = 0'
            . ' WHERE `' . $fk . '` = :update_id',
            ['update_id' => $update_id]
        );
    }

    /**
     * Remove every junction row that references a record being deleted
     * (delete hook).
     *
     * Junction-backed relations (one to one with a bridge, many to many)
     * store links in an `associated_{a}_and_{b}` table whose two FK
     * columns point at the two modules' tables. A junction row is
     * symmetric: either column may reference the record being deleted,
     * depending on which side's show page created the link. Both columns
     * are swept, so no junction row ever survives pointing at a record
     * that no longer exists.
     *
     * No-op for relation types without a junction table (direct one to
     * one, one to many), when the calling module isn't part of the
     * relation, and for invalid record ids.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose record is being deleted.
     * @param int    $update_id      The record id being deleted.
     * @return void
     */
    public function release_junction_links(array $settings, string $calling_module, int $update_id): void {
        if ($update_id < 1) {
            return;
        }
        $type = $settings[2]['relationship_type'] ?? '';
        if (($type === 'one to many') || $this->is_direct_one_to_one($settings)) {
            return; // No junction table for these types.
        }

        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return; // Not part of this relation.
        }

        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $junction = 'associated_' . $module_a . '_and_' . $module_b;
        $calling_fk = $this->junction_fk($settings, $calling_module);
        $this->db->query_bind(
            'DELETE FROM `' . $junction . '`'
            . ' WHERE `' . $calling_fk . '` = :update_id',
            ['update_id' => $update_id]
        );
    }

    // ============================================
    // Settings JSON (runtime contract)
    // ============================================

    /**
     * Read and decode the settings file for an unordered pair of modules.
     *
     * A relation between module A and module B is stored under one of two
     * possible filenames — `{a}_and_{b}.json` or `{b}_and_{a}.json` —
     * depending on which module the builder treated as "parent" at
     * generation time. Both are tried, in that order, so callers never
     * need to know or guess which order the file was written in.
     *
     * @param string $module_a First module name (either side of the pair).
     * @param string $module_b Second module name (either side of the pair).
     * @return array|null The decoded settings array (see the class
     *                    docblock for its shape), or null when either
     *                    module name is empty, no settings file exists for
     *                    the pair, or the file's contents don't decode to
     *                    a well-formed array.
     */
    public function get_relation_settings(string $module_a, string $module_b): ?array {
        if ($module_a === '' || $module_b === '') {
            return null;
        }

        $dir = APPPATH . self::SETTINGS_DIR;
        foreach ([$module_a . '_and_' . $module_b . '.json', $module_b . '_and_' . $module_a . '.json'] as $candidate) {
            $path = $dir . '/' . $candidate;
            if (!is_file($path)) {
                continue;
            }
            $json = file_get_contents($path);
            $settings = json_decode($json, true);
            if (!is_array($settings) || count($settings) < 3) {
                return null;
            }
            return $settings;
        }

        return null;
    }

    /**
     * The settings entry for the OTHER module in a relation — i.e. given
     * the module whose show page/form we're currently on, this returns the
     * module it is related to.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module we're currently operating
     *                               on behalf of.
     * @return array|null The associated module's settings entry, or null
     *                    when $calling_module isn't part of this relation.
     */
    public function est_associated_module(array $settings, string $calling_module): ?array {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        if ($calling_module === $module_a) {
            return $settings[1] ?? null;
        }
        if ($calling_module === $module_b) {
            return $settings[0] ?? null;
        }
        return null;
    }

    // ============================================
    // Runtime association data (show-page panels)
    // ============================================

    /**
     * The settings entry belonging to a specific module name.
     *
     * Unlike est_associated_module() (which returns the OTHER side), this
     * returns the entry for the module you name directly — used wherever
     * code needs the calling module's own record_name_singular etc.
     * rather than its partner's.
     *
     * @param array  $settings    The decoded relation settings array.
     * @param string $module_name The module name to look up.
     * @return array|null The matching entry, or null when $module_name
     *                    isn't part of this relation.
     */
    public function get_settings_for_module(array $settings, string $module_name): ?array {
        foreach ($settings as $entry) {
            if (($entry['module_name'] ?? '') === $module_name) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Whether the given settings describe a direct one-to-one relation —
     * i.e. one to one, WITHOUT a bridging/junction table (mirrored FK
     * columns on both tables instead).
     *
     * This is the gate used throughout this file to decide whether the
     * "no orphans" bidirectional claim/release logic applies; a bridged
     * one-to-one relation is handled as a junction-table case instead (see
     * submit_association()/disassociate_association()) since there both
     * sides are enforced by UNIQUE constraints on the junction table
     * rather than mirrored FK columns.
     *
     * @param array $settings The decoded relation settings array.
     * @return bool True when relationship_type is 'one to one' and
     *              bridging_table is false.
     */
    public function is_direct_one_to_one(array $settings): bool {
        return (($settings[2]['relationship_type'] ?? '') === 'one to one')
            && (($settings[2]['bridging_table'] ?? false) === false);
    }

    /**
     * Fetch the records currently associated with the calling record, for
     * display in the show-page panel.
     *
     * Row shape is always {id, foreign_key, value}:
     *   - id: for junction-backed relations, the junction table's own row
     *     id (needed so disassociate_association() can delete the right
     *     row); for non-junction relations, the associated record's id.
     *   - foreign_key: the associated record's id, always.
     *   - value: a human-readable display string built from the alt
     *     module's identifier column(s) — see identifier_expr().
     *
     * Delegates one-to-many handling to fetch_one_to_many_associated();
     * handles direct one-to-one and junction-backed relations inline.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @return array Rows as described above, or [] when $calling_module
     *               isn't part of the relation.
     */
    public function fetch_associated_rows(array $settings, string $calling_module, int $update_id): array {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $relationship_type = $settings[2]['relationship_type'] ?? '';

        if (($calling_module !== $module_a) && ($calling_module !== $module_b)) {
            return [];
        }

        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return [];
        }

        if ($relationship_type === 'one to many') {
            return $this->fetch_one_to_many_associated($settings, $calling_module, $update_id);
        }

        if ($this->is_direct_one_to_one($settings)) {
            // Mirrored FKs: the alt table's {calling_singular}_id column
            // points back at the calling record, so we look up the alt
            // record whose back-FK matches us, rather than reading our own
            // FK column directly — this keeps the query symmetric with the
            // junction-backed branch below, and works regardless of which
            // side's FK a caller happens to trust.
            $calling_entry = $this->get_settings_for_module($settings, $calling_module);
            $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id';
            $sql = 'SELECT alt.id AS id, alt.id AS foreign_key, ' . $this->identifier_expr($alt, 'alt') . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '` alt'
                 . ' INNER JOIN `' . $calling_module . '` calling ON calling.id = alt.`' . $back_fk . '`'
                 . ' WHERE calling.id = :update_id'
                 . ' ORDER BY ' . $this->identifier_order($alt, 'alt');
            return $this->db->query_bind($sql, ['update_id' => $update_id], 'array');
        }

        // Junction-backed (one to one with a bridge, or many to many).
        $junction = 'associated_' . $module_a . '_and_' . $module_b;
        $calling_fk = $this->junction_fk($settings, $calling_module);
        $alt_fk = $alt['record_name_singular'] . '_id';
        $sql = 'SELECT assoc.id AS id, assoc.`' . $alt_fk . '` AS foreign_key, ' . $this->identifier_expr($alt, 'alt') . ' AS value'
             . ' FROM `' . $junction . '` assoc'
             . ' INNER JOIN `' . $alt['module_name'] . '` alt ON alt.id = assoc.`' . $alt_fk . '`'
             . ' WHERE assoc.`' . $calling_fk . '` = :update_id'
             . ' ORDER BY ' . $this->identifier_order($alt, 'alt');
        return $this->db->query_bind($sql, ['update_id' => $update_id], 'array');
    }

    /**
     * Records that could be newly associated with the calling record —
     * i.e. the options offered in the show-page panel's "add" dropdown.
     *
     * Behaviour depends on relation type and, for one-to-many, which side
     * of the relation the calling module is on:
     *
     *   - one to many, parent view: unclaimed children only — children
     *     already linked to ANY parent (this one included) are not
     *     offered. Re-linking an assigned child is done from the child's
     *     own edit form, not this dropdown.
     *   - one to many, child view: [] — a child can only ever have one
     *     parent, chosen via its own create/edit form, not this dropdown.
     *   - one to one, no bridge: unclaimed alt records only (back-FK NULL).
     *   - one to one, with bridge: alt records not linked to ANY junction
     *     row yet (both sides of a bridged 1:1 are capped at one link each).
     *   - many to many: all alt records except ones already linked to this
     *     specific calling record (an alt record may be linked to many
     *     different calling records).
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @return array Options shaped as {key, value} pairs (note: NOT the
     *               form_dropdown() [key => value] map used by
     *               fetch_create_options() — this is a plain list, built
     *               for the panel view to loop over).
     */
    public function fetch_available_options(array $settings, string $calling_module, int $update_id): array {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $relationship_type = $settings[2]['relationship_type'] ?? '';

        if (($calling_module !== $module_a) && ($calling_module !== $module_b)) {
            return [];
        }

        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return [];
        }

        if ($relationship_type === 'one to many') {
            if ($calling_module === $module_b) {
                return []; // Child view: no association dropdown.
            }
            $fk_a = $settings[0]['record_name_singular'] . '_id';
            $sql = 'SELECT id, ' . $this->identifier_expr($alt, $alt['module_name']) . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '`'
                 . ' WHERE (`' . $fk_a . '` IS NULL OR `' . $fk_a . '` = 0)'
                 . ' ORDER BY ' . $this->identifier_order($alt, $alt['module_name']);
            return $this->rows_to_options($this->db->query_bind($sql, [], 'array'));
        }

        if ($this->is_direct_one_to_one($settings)) {
            // Unclaimed alt records only — the back-FK is 0 (the schema
            // default since refs #14, and what the runtime writes on
            // release). Legacy NULL rows from before the 0-sentinel
            // change are still treated as unclaimed.
            $calling_entry = $this->get_settings_for_module($settings, $calling_module);
            $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id';
            $sql = 'SELECT id, ' . $this->identifier_expr($alt, $alt['module_name']) . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '`'
                 . ' WHERE (`' . $back_fk . '` IS NULL OR `' . $back_fk . '` = 0)'
                 . ' ORDER BY ' . $this->identifier_order($alt, $alt['module_name']);
            return $this->rows_to_options($this->db->query_bind($sql, [], 'array'));
        }

        $junction = 'associated_' . $module_a . '_and_' . $module_b;
        $calling_fk = $this->junction_fk($settings, $calling_module);
        $alt_fk = $alt['record_name_singular'] . '_id';

        if ($relationship_type === 'many to many') {
            // All alt records minus the ones already linked to THIS calling
            // record specifically (an alt record may still be linked to
            // other calling records — that's expected for many to many).
            $sql = 'SELECT alt.id, ' . $this->identifier_expr($alt, 'alt') . ' AS value'
                 . ' FROM `' . $alt['module_name'] . '` alt'
                 . ' WHERE alt.id NOT IN (SELECT assoc.`' . $alt_fk . '` FROM `' . $junction . '` assoc'
                 . ' WHERE assoc.`' . $calling_fk . '` = :update_id)'
                 . ' ORDER BY ' . $this->identifier_order($alt, 'alt');
            return $this->rows_to_options($this->db->query_bind($sql, ['update_id' => $update_id], 'array'));
        }

        // One to one, with bridge: alt records with no junction row at all
        // (a bridged one-to-one caps BOTH sides at a single link).
        $sql = 'SELECT alt.id, ' . $this->identifier_expr($alt, 'alt') . ' AS value'
             . ' FROM `' . $alt['module_name'] . '` alt'
             . ' LEFT JOIN `' . $junction . '` assoc ON assoc.`' . $alt_fk . '` = alt.id'
             . ' WHERE assoc.`' . $alt_fk . '` IS NULL'
             . ' ORDER BY ' . $this->identifier_order($alt, 'alt');
        return $this->rows_to_options($this->db->query_bind($sql, [], 'array'));
    }

    /**
     * Create an association from the show-page panel's "add" form.
     *
     * Behaviour per relation type:
     *   - one to many, parent view: point the chosen child's FK at this parent.
     *   - one to many, child view: point this child's FK at the chosen parent.
     *   - one to one, no bridge: delegates to submit_direct_one_to_one() for
     *     the full bidirectional claim/release (no orphans on either side).
     *   - one to one, with bridge: clears any existing junction row on
     *     EITHER side first (since both sides are capped at one link), then
     *     inserts the new junction row.
     *   - many to many: an idempotent no-op when the pair is already
     *     linked (the composite UNIQUE would refuse the duplicate insert
     *     anyway — checking first keeps re-submits harmless), then a
     *     plain insert.
     *
     * Junction-backed branches (one to one with a bridge, many to many)
     * also verify both records exist BEFORE touching any junction row — a
     * forged POST with a made-up id must never create a dangling junction
     * row (or, for bridged 1:1, unlink a real pair it was never entitled
     * to touch).
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @param int    $value          The record id to associate.
     * @return bool True on success (including idempotent re-submits); false
     *              when $calling_module isn't part of the relation, either
     *              id is 0, or (junction-backed only) either record does
     *              not exist.
     */
    public function submit_association(array $settings, string $calling_module, int $update_id, int $value): bool {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $relationship_type = $settings[2]['relationship_type'] ?? '';

        if (($calling_module !== $module_a) && ($calling_module !== $module_b)) {
            return false;
        }
        if ($update_id === 0 || $value === 0) {
            return false;
        }

        // $alt is guaranteed non-null here: est_associated_module() returns
        // null only when $calling_module matches neither settings entry,
        // which the guard above already excludes. The explicit check keeps
        // that invariant locally visible — safe even if branches are
        // reordered later.
        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return false;
        }

        if ($relationship_type === 'one to many') {
            $fk_a = $settings[0]['record_name_singular'] . '_id';
            if ($calling_module === $module_a) {
                // Parent view: point the chosen child at this parent —
                // but only while the child is unclaimed (or already
                // ours). A child that belongs to a different parent must
                // not be silently moved through the panel; re-parenting
                // happens from the child's own edit form.
                $child = $this->db->query_bind(
                    'SELECT `' . $fk_a . '` AS owner_id FROM `' . $module_b . '` WHERE id = :value',
                    ['value' => $value],
                    'array'
                );
                $owner_id = (int) ($child[0]['owner_id'] ?? -1);
                if ($owner_id === $update_id) {
                    return true; // Already linked — idempotent no-op.
                }
                if ($owner_id !== 0) {
                    return false; // Claimed by another parent (or missing row).
                }
                $sql = 'UPDATE `' . $module_b . '` SET `' . $fk_a . '` = :update_id WHERE id = :value';
                $this->db->query_bind($sql, ['update_id' => $update_id, 'value' => $value]);
                return true;
            }
            // Child view: point this child at the chosen parent.
            $sql = 'UPDATE `' . $module_b . '` SET `' . $fk_a . '` = :value WHERE id = :update_id';
            $this->db->query_bind($sql, ['update_id' => $update_id, 'value' => $value]);
            return true;
        }

        if ($this->is_direct_one_to_one($settings)) {
            $this->submit_direct_one_to_one($settings, $calling_module, $update_id, $value);
            return true;
        }

        // Junction-backed (one to one with a bridge, or many to many).
        $junction = 'associated_' . $module_a . '_and_' . $module_b;
        $calling_fk = $this->junction_fk($settings, $calling_module);
        $alt_fk = $alt['record_name_singular'] . '_id';

        // Defensive existence checks: both records must exist before a
        // junction row may reference them (see the method docblock).
        $calling_row = $this->db->query_bind(
            'SELECT id FROM `' . $calling_module . '` WHERE id = :update_id',
            ['update_id' => $update_id],
            'array'
        );
        $alt_row = $this->db->query_bind(
            'SELECT id FROM `' . $alt['module_name'] . '` WHERE id = :value',
            ['value' => $value],
            'array'
        );
        if ((count($calling_row) === 0) || (count($alt_row) === 0)) {
            return false;
        }

        if ($relationship_type === 'many to many') {
            // Idempotent no-op: an already-linked pair is a success, not
            // an error — mirroring the 1:N branch. The composite UNIQUE
            // key would also refuse the duplicate insert, but that would
            // surface as a raw duplicate-key failure; checking first keeps
            // re-submits (double-clicks, MX retries) harmless.
            $existing = $this->db->query_bind(
                'SELECT id FROM `' . $junction . '`'
                . ' WHERE `' . $calling_fk . '` = :update_id'
                . ' AND `' . $alt_fk . '` = :value',
                ['update_id' => $update_id, 'value' => $value],
                'array'
            );
            if (count($existing) > 0) {
                return true;
            }
        } else {
            // Bridged one to one: the calling record may hold at most one
            // junction row, and the chosen alt record may be claimed by at
            // most one calling record. Clear any existing row on either
            // side before inserting the new one, so the insert can never
            // create a second link for either party.
            $this->db->query_bind('DELETE FROM `' . $junction . '` WHERE `' . $calling_fk . '` = :update_id', ['update_id' => $update_id]);
            $this->db->query_bind('DELETE FROM `' . $junction . '` WHERE `' . $alt_fk . '` = :value', ['value' => $value]);
        }

        $this->db->insert([$calling_fk => $update_id, $alt_fk => $value], $junction);
        return true;
    }

    /**
     * Remove an association from the show-page panel.
     *
     * Behaviour per relation type:
     *   - one to many: clears the child's FK (NULL). $value is the child's
     *     own id in this case, since removal is always initiated from
     *     whichever side's panel lists the link.
     *   - one to one, no bridge: clears BOTH mirrored FKs. $value is the
     *     alt record's id.
     *   - junction-backed: deletes the junction row outright. $value is
     *     the junction row's own id (as returned by fetch_associated_rows()'s
     *     `id` field for this relation type — NOT the alt record's id).
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @param int    $value          The id to act on — its meaning depends
     *                               on relation type; see above. Always the
     *                               exact `id` value the panel received
     *                               from fetch_associated_rows().
     * @return bool True on success; false when $calling_module isn't part
     *              of the relation, or either id is 0.
     */
    public function disassociate_association(array $settings, string $calling_module, int $update_id, int $value): bool {
        $module_a = $settings[0]['module_name'] ?? '';
        $module_b = $settings[1]['module_name'] ?? '';
        $relationship_type = $settings[2]['relationship_type'] ?? '';

        if (($calling_module !== $module_a) && ($calling_module !== $module_b)) {
            return false;
        }
        if ($update_id === 0 || $value === 0) {
            return false;
        }

        // $alt is guaranteed non-null here (same invariant as
        // submit_association()): est_associated_module() returns null only
        // when $calling_module matches neither settings entry, which the
        // guard above already excludes. Explicit check keeps it locally
        // visible and safe against future branch reordering.
        $alt = $this->est_associated_module($settings, $calling_module);
        if ($alt === null) {
            return false;
        }

        if ($relationship_type === 'one to many') {
            // Clear the child's FK (value = child id).
            $sql = 'UPDATE `' . $module_b . '` SET `' . $settings[0]['record_name_singular'] . '_id` = 0 WHERE id = :value';
            $this->db->query_bind($sql, ['value' => $value]);
            return true;
        }

        if ($this->is_direct_one_to_one($settings)) {
            // Clear both mirrored FKs (value = alt id).
            $calling_entry = $this->get_settings_for_module($settings, $calling_module);
            $alt_fk = $alt['record_name_singular'] . '_id';            // on calling table
            $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id'; // on alt table
            $this->db->query_bind('UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = 0 WHERE id = :update_id', ['update_id' => $update_id]);
            $this->db->query_bind('UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = 0 WHERE id = :value', ['value' => $value]);
            return true;
        }

        // Junction-backed: delete the junction row (value = junction row id).
        $junction = 'associated_' . $module_a . '_and_' . $module_b;
        $this->db->query_bind('DELETE FROM `' . $junction . '` WHERE id = :value', ['value' => $value]);
        return true;
    }

    // ─── Private Helpers ───────────────────────────────────────

    /**
     * fetch_associated_rows() logic specific to one-to-many relations,
     * covering both the parent's and the child's view of the relationship.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @return array Rows in the same {id, foreign_key, value} shape
     *               documented on fetch_associated_rows().
     */
    private function fetch_one_to_many_associated(array $settings, string $calling_module, int $update_id): array {
        $module_a = $settings[0]['module_name'];
        $module_b = $settings[1]['module_name'];
        $fk_a = $settings[0]['record_name_singular'] . '_id';

        if ($calling_module === $module_a) {
            // Parent view: every child currently linked to this parent
            // (a parent may have many children).
            $sql = 'SELECT child.id AS id, child.id AS foreign_key, ' . $this->identifier_expr($settings[1], 'child') . ' AS value'
                 . ' FROM `' . $module_b . '` child'
                 . ' WHERE child.`' . $fk_a . '` = :update_id'
                 . ' ORDER BY ' . $this->identifier_order($settings[1], 'child');
            return $this->db->query_bind($sql, ['update_id' => $update_id], 'array');
        }

        // Child view: the single parent this child belongs to, if any
        // (a child has at most one parent, so this returns 0 or 1 rows).
        $sql = 'SELECT child.id AS id, parent.id AS foreign_key, ' . $this->identifier_expr($settings[0], 'parent') . ' AS value'
             . ' FROM `' . $module_a . '` parent'
             . ' INNER JOIN `' . $module_b . '` child ON child.`' . $fk_a . '` = parent.id'
             . ' WHERE child.id = :update_id';
        return $this->db->query_bind($sql, ['update_id' => $update_id], 'array');
    }

    /**
     * The full bidirectional claim/release for a direct (non-bridged)
     * one-to-one association, triggered from the show-page panel's "add"
     * form.
     *
     * Whenever a record claims a new partner, any partner it previously
     * held is released on both sides, and if the newly-claimed record
     * already belonged to someone else, that link is released too — so a
     * direct one-to-one relation can never end up with a dangling FK on
     * either table, no matter which side initiates the change.
     *
     * This mirrors sync_create_claim()'s logic; it exists separately
     * because this path runs entirely within a single call (no pre-edit
     * $prev_value needs to be threaded in from a controller — the previous
     * partner, if any, is looked up here directly).
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module whose show page this is.
     * @param int    $update_id      The calling record's id.
     * @param int    $value          The alt record id being claimed.
     * @return void
     */
    private function submit_direct_one_to_one(array $settings, string $calling_module, int $update_id, int $value): void {
        $calling_entry = $this->get_settings_for_module($settings, $calling_module);
        $alt = $this->est_associated_module($settings, $calling_module);
        $alt_fk = $alt['record_name_singular'] . '_id';            // column on the calling table
        $back_fk = ($calling_entry['record_name_singular'] ?? '') . '_id'; // column on the alt table

        // Release the calling record's previous partner (both sides), if any.
        $prev = $this->db->query_bind('SELECT `' . $alt_fk . '` AS alt_id FROM `' . $calling_module . '` WHERE id = :update_id', ['update_id' => $update_id], 'array');
        $prev_alt_id = (int) ($prev[0]['alt_id'] ?? 0);
        if ($prev_alt_id > 0) {
            $this->db->query_bind('UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = 0 WHERE id = :prev_alt_id', ['prev_alt_id' => $prev_alt_id]);
        }
        $this->db->query_bind('UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = 0 WHERE id = :update_id', ['update_id' => $update_id]);

        // Release the chosen alt record from any previous owner (both sides), if any.
        $owner = $this->db->query_bind('SELECT `' . $back_fk . '` AS owner_id FROM `' . $alt['module_name'] . '` WHERE id = :value', ['value' => $value], 'array');
        $prev_owner_id = (int) ($owner[0]['owner_id'] ?? 0);
        if ($prev_owner_id > 0) {
            $this->db->query_bind('UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = 0 WHERE id = :prev_owner_id', ['prev_owner_id' => $prev_owner_id]);
        }

        // Link both sides.
        $this->db->query_bind('UPDATE `' . $calling_module . '` SET `' . $alt_fk . '` = :value WHERE id = :update_id', ['update_id' => $update_id, 'value' => $value]);
        $this->db->query_bind('UPDATE `' . $alt['module_name'] . '` SET `' . $back_fk . '` = :update_id WHERE id = :value', ['update_id' => $update_id, 'value' => $value]);
    }

    /**
     * The junction table's FK column name for a given calling module.
     *
     * Junction tables always name their two FK columns
     * {singular_a}_id / {singular_b}_id, so this is just a lookup of the
     * calling module's own record_name_singular, with '_id' appended.
     *
     * @param array  $settings       The decoded relation settings array.
     * @param string $calling_module The module to get the FK column name for.
     * @return string The FK column name (e.g. 'actor_id'), or '_id' when
     *                $calling_module isn't found in $settings.
     */
    private function junction_fk(array $settings, string $calling_module): string {
        $entry = $this->get_settings_for_module($settings, $calling_module);
        return ($entry['record_name_singular'] ?? '') . '_id';
    }

    /**
     * Validate a module's identifier_column setting against that module's
     * actual table columns, returning only the parts that check out.
     *
     * identifier_column supports a comma-separated list (for records whose
     * display value is built from more than one column, e.g. first + last
     * name). Each part must both match a strict column-name pattern AND
     * exist on the real table — this is the runtime re-validation
     * mentioned in the class docblock's trust-boundary note: unlike
     * module_name/record_name_singular, identifier_column values are
     * checked here, every time, before being used in a query.
     *
     * @param array  $entry The module's settings entry (must contain
     *                      'identifier_column' and 'module_name').
     * @param string $table The table name to validate columns against
     *                      (normally the same as $entry['module_name']).
     * @return array<string> The identifier_column parts that are both
     *                       well-formed and real columns on $table, in
     *                       their original order. [] when none are valid.
     */
    private function valid_identifier_columns(array $entry, string $table): array {
        $raw = (string) ($entry['identifier_column'] ?? '');
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_filter($parts, fn($p) => preg_match('/^[a-z0-9_]+$/', $p) === 1);

        if (count($parts) === 0) {
            return [];
        }

        $real = $this->get_table_columns($table);
        $valid = [];
        foreach ($parts as $part) {
            if (in_array($part, $real, true)) {
                $valid[] = $part;
            }
        }
        return $valid;
    }

    /**
     * SQL expression for a record's human-readable display value.
     *
     * Multiple valid identifier columns are joined with CONCAT_WS() so
     * e.g. first_name + last_name renders as a single readable string.
     * Falls back to the record's own id when no identifier column on the
     * entry is valid, so display never breaks even with a misconfigured
     * or stale settings entry — it just falls back to showing the id.
     *
     * @param array  $entry The module's settings entry.
     * @param string $alias The SQL table alias to prefix each column with.
     * @return string A backticked, alias-prefixed SQL expression — safe to
     *                interpolate directly into a SELECT list because every
     *                column name it uses has already passed
     *                valid_identifier_columns()'s whitelist check.
     */
    private function identifier_expr(array $entry, string $alias): string {
        $cols = $this->valid_identifier_columns($entry, $entry['module_name']);
        if (count($cols) === 0) {
            return '`' . $alias . '`.`id`';
        }
        $quoted = array_map(fn($c) => '`' . $alias . '`.`' . $c . '`', $cols);
        return 'CONCAT_WS(\' \', ' . implode(', ', $quoted) . ')';
    }

    /**
     * ORDER BY expression matching identifier_expr()'s display value.
     *
     * Deliberately sorts by only the LAST declared identifier column
     * (e.g. for "first_name, last_name", this sorts by last_name) rather
     * than the full concatenated string — sorting by a surname reads as
     * more natural alphabetical order than sorting by a full "First Last"
     * string would.
     *
     * @param array  $entry The module's settings entry.
     * @param string $alias The SQL table alias to prefix the column with.
     * @return string A backticked, alias-prefixed ORDER BY expression,
     *                whitelisted the same way as identifier_expr().
     */
    private function identifier_order(array $entry, string $alias): string {
        $cols = $this->valid_identifier_columns($entry, $entry['module_name']);
        if (count($cols) === 0) {
            return '`' . $alias . '`.`id`';
        }
        return '`' . $alias . '`.`' . end($cols) . '`';
    }

    /**
     * Convert {id, value} query rows into the PHP form_dropdown() contract.
     *
     * @param array $rows Query rows, each with an 'id' and a 'value' key.
     * @return array<int|string,string> [id => value] pairs, suitable for
     *                                  passing straight to form_dropdown().
     */
    private function options_to_dropdown(array $rows): array {
        $options = [];
        foreach ($rows as $row) {
            $options[(int) $row['id']] = $row['value'];
        }
        return $options;
    }

    /**
     * Convert {id, value} query rows into a plain list of {key, value}
     * option arrays, for the show-page panel view to loop over directly
     * (as opposed to options_to_dropdown()'s [key => value] map, which
     * form_dropdown() expects instead).
     *
     * @param array $rows Query rows, each with an 'id' and a 'value' key.
     * @return array List of ['key' => int, 'value' => string] entries.
     */
    private function rows_to_options(array $rows): array {
        $options = [];
        foreach ($rows as $row) {
            $options[] = ['key' => (int) $row['id'], 'value' => $row['value']];
        }
        return $options;
    }

}