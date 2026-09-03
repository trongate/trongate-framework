<?php
/**
 * Module_relations_builder_model - Data layer for Flo's module relations wizard.
 *
 * Table eligibility, identifier-column defaults, settings JSON write, and
 * schema SQL generation for the relation being created.
 *
 * Generation-time only, like its controller. The runtime counterpart lives
 * in the top-level module_relations module; this model decides WHAT gets
 * created (schema + settings), the controller decides WHERE code is injected.
 */
class Module_relations_builder_model extends Model {

    /**
     * Valid relation type keys (mirrors the controller + options list).
     */
    private const RELATION_TYPES = ['one to one', 'one to many', 'many to many'];

    /**
     * Framework/system tables never offered as relation endpoints.
     */
    private const SYSTEM_TABLES = [
        'login_attempts',
        'password_resets',
        'welcome'
    ];

    /**
     * The runtime module's settings directory (relative to APPPATH).
     * Settings live in the app's standalone module_relations runtime module —
     * versioned, and decoupled from trongate_control.
     */
    private const SETTINGS_DIR = 'modules/module_relations/settings';

    /**
     * The module_relations runtime route prefix used by ALL generated code
     * (single source of truth — centralised here so a future move of the
     * runtime module is a one-line change, not a re-injection of every
     * generated file). Value deliberately unchanged from the parked
     * injector's output: generated code stays byte-identical.
     */
    public const RUNTIME_ROUTE = 'module_relations';

    /**
     * Getter for the runtime route (controllers reach $this->model through
     * the base Model proxy, so class-constant access via :: resolves against
     * Model — a method call proxies correctly instead).
     *
     * @return string The runtime route prefix.
     */
    public function get_runtime_route(): string {
        return self::RUNTIME_ROUTE;
    }

    // ============================================
    // Injection vocabulary
    // ============================================

    /**
     * The FK column name for a module's singular name (e.g. actor → actor_id).
     *
     * @param string $singular The singular module name.
     * @return string The FK column name.
     */
    public function fk_name(string $singular): string {
        return $singular . '_id';
    }

    /**
     * The dropdown-options variable name for a module's singular name.
     *
     * @param string $singular The singular module name.
     * @return string The options variable name.
     */
    public function options_var(string $singular): string {
        return $singular . '_options';
    }

    /**
     * Human-readable label for a singular name (e.g. academy_award →
     * Academy Award).
     *
     * @param string $singular The singular module name.
     * @return string The display label.
     */
    public function label(string $singular): string {
        return ucwords(str_replace('_', ' ', $singular));
    }

    /**
     * The flo_relation marker comment prefix for an FK (double-inject
     * guard — preflight refuses any file already containing a marker).
     * Call sites prepend the comment slashes themselves (templates and
     * plan markers both build "// " . marker . suffix).
     *
     * NOTE: the injectable templates hardcode this literal prefix in their
     * own marker comments (`// flo_relation: <?= $fk ?> …` in
     * sync_on_save_create, sync_on_save_update, prev_capture, delete_hook,
     * parent_delete_hook) rather than receiving it as data — if this
     * format ever changes, those five templates must change with it.
     *
     * @param string $fk The FK column name.
     * @return string The marker prefix (e.g. "flo_relation: actor_id ").
     */
    public function marker(string $fk): string {
        return 'flo_relation: ' . $fk . ' ';
    }

    /**
     * Decode entity-escaped PHP tags in rendered code templates (the
     * site_builder pattern: templates write &lt;?php / &lt;?= as entities so
     * they are not executed at render time; this restores them for output).
     *
     * @param string $content Rendered template content.
     * @return string Content with PHP tags restored.
     */
    public function prep_file_contents(string $content): string {
        return str_replace(
            ['&lt;', '&gt;'],
            ['<', '>'],
            $content
        );
    }

    // ============================================
    // Eligibility
    // ============================================

    /**
     * List all modules (database tables) eligible for relations.
     *
     * A module is eligible when ALL of the following hold:
     *   - its table exists and is a BASE TABLE
     *   - its controller file exists (module folder = class name)
     *   - the table has an `id` primary key
     *   - it is not a framework/system module (trongate_*, system list)
     *   - it is not the module_relations runtime itself
     *   - its name contains no dots/spaces and does not start with
     *     `associated_` (v1's association-table convention)
     *
     * @return array<string> Eligible table names, sorted.
     */
    public function get_relation_tables(): array {
        $rows = $this->db->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME",
            'object'
        );

        $tables = [];
        foreach ($rows as $row) {
            $name = $row->TABLE_NAME;
            if ($this->is_eligible($name)) {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    /**
     * Whether the given table name is a valid relation endpoint.
     *
     * @param string $table Table name.
     * @return bool True when the table is eligible.
     */
    public function is_relation_table(string $table): bool {
        return in_array($table, $this->get_relation_tables(), true);
    }

    /**
     * Eligibility check for a single table name.
     *
     * @param string $name Table name.
     * @return bool True when eligible.
     */
    private function is_eligible(string $name): bool {
        if (str_starts_with($name, 'trongate_')) {
            return false;
        }
        if (in_array($name, self::SYSTEM_TABLES, true)) {
            return false;
        }
        if ($name === 'module_relations') {
            return false; // the runtime module itself
        }
        if ((strpos($name, '.') !== false) || (strpos($name, ' ') !== false)) {
            return false;
        }
        if (str_starts_with($name, 'associated_')) {
            return false; // v1 association-table convention
        }
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            return false;
        }

        // Controller file must exist (module folder = class name).
        $controller = APPPATH . 'modules/' . $name . '/' . ucfirst($name) . '.php';
        if (!is_file($controller)) {
            return false;
        }

        // Table must have an `id` primary key.
        $rows = $this->db->query_bind(
            "SELECT COUNT(*) AS cnt FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND CONSTRAINT_NAME = 'PRIMARY'
               AND COLUMN_NAME = 'id'",
            ['table' => $name],
            'object'
        );
        return (int) ($rows[0]->cnt ?? 0) > 0;
    }

    /**
     * Derive a sensible default identifier column for a table.
     *
     * Prefers the first textual column (varchar/char/text/email variants);
     * falls back to the first non-id column.
     *
     * @param string $table Table name.
     * @return string The default identifier column name ('' when none).
     */
    public function get_default_identifier_column(string $table): string {
        if ($table === '' || !preg_match('/^[a-z0-9_]+$/', $table)) {
            return '';
        }

        $rows = $this->db->query_bind(
            "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table
               AND COLUMN_NAME != 'id'
             ORDER BY ORDINAL_POSITION",
            ['table' => $table],
            'object'
        );

        $textual = ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'email'];
        $fallback = '';

        foreach ($rows as $row) {
            $col = $row->COLUMN_NAME;
            if ($fallback === '') {
                $fallback = $col;
            }
            if (in_array($row->DATA_TYPE, $textual, true)) {
                return $col;
            }
        }

        return $fallback;
    }

    /**
     * Validate an identifier column string against a table's real columns.
     *
     * Public so the controller's validation callbacks can delegate here
     * (single source of truth — no duplicated logic). Comma-separated
     * multi-column identifiers are allowed; every part must exist.
     *
     * @param string $identifier The identifier column (possibly comma-separated).
     * @param string $table      The table name.
     * @return bool True when every part exists on the table.
     */
    public function identifier_columns_valid(string $identifier, string $table): bool {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return false;
        }
        $columns = $this->get_table_columns($table);
        $parts = explode(',', $identifier);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || !in_array($part, $columns, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * List the real column names of a table.
     *
     * @param string $table Table name (backticked safely in SQL).
     * @return array<string> Column names.
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

    /**
     * Whether a column already exists on a table.
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return bool True when the column exists.
     */
    public function column_exists(string $table, string $column): bool {
        $rows = $this->db->query_bind(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column",
            ['table' => $table, 'column' => $column],
            'object'
        );
        return (int) ($rows[0]->cnt ?? 0) > 0;
    }

    /**
     * Whether a table already exists in the database.
     *
     * @param string $table Table name.
     * @return bool True when the table exists.
     */
    public function table_exists(string $table): bool {
        $rows = $this->db->query_bind(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
            ['table' => $table],
            'object'
        );
        return (int) ($rows[0]->cnt ?? 0) > 0;
    }

    /**
     * Whether a relation settings file already exists for a module pair
     * (checked in both orders — a relation is unique per unordered pair).
     *
     * @param string $module_a First module name.
     * @param string $module_b Second module name.
     * @return bool True when a settings file exists for the pair.
     */
    public function relation_exists(string $module_a, string $module_b): bool {
        $dir = APPPATH . self::SETTINGS_DIR;
        foreach ([$module_a . '_and_' . $module_b . '.json', $module_b . '_and_' . $module_a . '.json'] as $candidate) {
            if (is_file($dir . '/' . $candidate)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Run the complete eligibility pre-flight for a relation.
     *
     * Every check happens BEFORE any mutation (no settings write, no SQL).
     * Any failure throws \Exception with a clean, actionable message.
     *
     * @param array $wizard The wizard session array.
     * @return void
     * @throws \Exception On the first failed check.
     */
    public function guard_relation_ready(array $wizard): void {
        $type = $wizard['relation_type'] ?? '';
        $parent = $wizard['parent_module'] ?? '';
        $child = $wizard['child_module'] ?? '';

        if (!in_array($type, self::RELATION_TYPES, true)) {
            throw new \Exception('Please choose a valid relation type.');
        }
        if ($parent === '' || $child === '') {
            throw new \Exception('Both modules must be selected before generating a relation.');
        }
        if ($parent === $child) {
            throw new \Exception('The two modules must be different.');
        }
        if (!$this->is_relation_table($parent)) {
            throw new \Exception("Module '{$parent}' is not eligible for a module relation (no table, no id primary key, or it is a framework module).");
        }
        if (!$this->is_relation_table($child)) {
            throw new \Exception("Module '{$child}' is not eligible for a module relation (no table, no id primary key, or it is a framework module).");
        }
        if ($this->relation_exists($parent, $child)) {
            throw new \Exception("A relation already exists between '{$parent}' and '{$child}'.");
        }

        // Identifier columns must exist on their tables.
        $identifier_a = trim($wizard['identifier_column_a'] ?? '');
        $identifier_b = trim($wizard['identifier_column_b'] ?? '');
        if (!$this->identifier_columns_valid($identifier_a, $parent)) {
            throw new \Exception("Identifier column '{$identifier_a}' does not exist on the {$parent} table.");
        }
        if (!$this->identifier_columns_valid($identifier_b, $child)) {
            throw new \Exception("Identifier column '{$identifier_b}' does not exist on the {$child} table.");
        }

        // Schema conflicts (per type).
        $singular_a = $wizard['singular_a'] ?? $this->fallback_singular($parent);
        $singular_b = $wizard['singular_b'] ?? $this->fallback_singular($child);
        $bridging = (bool) ($wizard['bridging_table'] ?? false);

        // Singular names become FK/junction column names — enforce the same
        // identifier charset as the table-name checks (defense in depth: the
        // junction branch concatenates them straight into CREATE TABLE).
        if (!preg_match('/^[a-z0-9_]+$/', $singular_a) || !preg_match('/^[a-z0-9_]+$/', $singular_b)) {
            throw new \Exception('Singular module names may contain lowercase letters, numbers, and underscores only.');
        }

        if ($type === 'one to many') {
            $fk = $singular_a . '_id';
            $this->guard_fk_free($child, $fk);
        } elseif ($type === 'one to one' && !$bridging) {
            $this->guard_fk_free($parent, $singular_b . '_id');
            $this->guard_fk_free($child, $singular_a . '_id');
        } else {
            // one to one (with bridging) and many to many → junction table
            $junction = 'associated_' . $parent . '_and_' . $child;
            if (strlen($junction) > 64) {
                throw new \Exception("The junction table name '{$junction}' exceeds MySQL's 64-character identifier limit. Shorten the module names.");
            }
            if ($this->table_exists($junction)) {
                throw new \Exception("A table named '{$junction}' already exists.");
            }
        }
    }

    /**
     * Write the relation settings JSON into the app's module_relations
     * runtime module (v1 settings format + bridging_table flag).
     *
     * @param array $wizard The wizard session array.
     * @return string The settings file path written.
     * @throws \Exception When the file cannot be written.
     */
    public function write_settings_file(array $wizard): string {
        $parent = $wizard['parent_module'] ?? '';
        $child = $wizard['child_module'] ?? '';
        $type = $wizard['relation_type'] ?? '';
        $bridging = (bool) ($wizard['bridging_table'] ?? false);

        $settings = [
            [
                'module_name' => $parent,
                'record_name_singular' => $wizard['singular_a'] ?? $this->fallback_singular($parent),
                'record_name_plural' => $wizard['plural_a'] ?? $parent,
                'identifier_column' => trim($wizard['identifier_column_a'] ?? '')
            ],
            [
                'module_name' => $child,
                'record_name_singular' => $wizard['singular_b'] ?? $this->fallback_singular($child),
                'record_name_plural' => $wizard['plural_b'] ?? $child,
                'identifier_column' => trim($wizard['identifier_column_b'] ?? '')
            ],
            [
                'relationship_type' => $type,
                'bridging_table' => $bridging
            ]
        ];

        $dir = APPPATH . self::SETTINGS_DIR;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new \Exception('The module_relations runtime settings directory could not be created: ' . $dir);
            }
            // PHP runs as daemon under XAMPP and umask yields 0755 —
            // explicitly widen so the developer shell can manage these
            // files too (same class of issue as templates/admin.php).
            @chmod($dir, 0777);
        }
        if (!is_writable($dir)) {
            throw new \Exception('The module_relations runtime settings directory is not writable: ' . $dir);
        }

        $path = $dir . '/' . $parent . '_and_' . $child . '.json';
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($path, $json) === false) {
            throw new \Exception('Could not write the relation settings file: ' . $path);
        }
        @chmod($path, 0777);

        return $path;
    }

    /**
     * Delete the relation settings file for a module pair (if present).
     *
     * Used by the controller's run_gen() catch block: a settings file must
     * never survive a failed generation, or a retry would be blocked by the
     * duplicate-relation guard (the settings file IS the existence marker).
     *
     * @param array $wizard The wizard session array.
     * @return void
     */
    public function delete_settings_file(array $wizard): void {
        $parent = $wizard['parent_module'] ?? '';
        $child = $wizard['child_module'] ?? '';
        if ($parent === '' || $child === '') {
            return;
        }
        $dir = APPPATH . self::SETTINGS_DIR;
        $path = $dir . '/' . $parent . '_and_' . $child . '.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Build the schema SQL statements for a relation.
     *
     * Returns an ARRAY of single statements (Db::query executes one
     * statement per call — no multi-statement strings). All identifiers
     * backticked; FK columns INT DEFAULT 0 with a named index; junction
     * tables created here at generation time, never lazily at runtime.
     *
     * @param array $wizard The wizard session array.
     * @return array<string> SQL statements, in execution order.
     * @throws \Exception On invalid wizard data (guards already ran).
     */
    public function build_relation_sql(array $wizard): array {
        $type = $wizard['relation_type'] ?? '';
        $parent = $wizard['parent_module'] ?? '';
        $child = $wizard['child_module'] ?? '';
        $singular_a = $wizard['singular_a'] ?? $this->fallback_singular($parent);
        $singular_b = $wizard['singular_b'] ?? $this->fallback_singular($child);
        $bridging = (bool) ($wizard['bridging_table'] ?? false);

        if ($parent === '' || $child === '') {
            throw new \Exception('Relation could not be built because the parent and child modules were not provided.');
        }

        if ($type === 'one to many') {
            return [$this->build_alter_add_fk($child, $singular_a . '_id')];
        }

        if ($type === 'one to one' && !$bridging) {
            return [
                $this->build_alter_add_fk($parent, $singular_b . '_id'),
                $this->build_alter_add_fk($child, $singular_a . '_id')
            ];
        }

        // one to one (with bridging) and many to many → junction table
        return [$this->build_junction_sql($parent, $child, $singular_a, $singular_b, $type)];
    }

    // ─── Private generation helpers ────────────────────────────

    /**
     * Guard that an FK column name is well-formed and not already taken.
     *
     * @param string $table The table that would receive the column.
     * @param string $fk    The FK column name.
     * @return void
     * @throws \Exception When the column is invalid or already exists.
     */
    private function guard_fk_free(string $table, string $fk): void {
        if (!preg_match('/^[a-z0-9_]+$/', $fk)) {
            throw new \Exception("Foreign key column '{$fk}' is not a valid column name.");
        }
        if ($this->column_exists($table, $fk)) {
            throw new \Exception("Column '{$fk}' already exists on the {$table} table.");
        }
    }

    /**
     * Build ALTER TABLE SQL adding a soft FK column + named index.
     *
     * The column defaults to 0 (int) — the 'no parent' sentinel: the
     * create-form dropdown's '— None —' option is key 0, and generated
     * get_data_from_post() maps a posted 0 to 0, so unassigned rows store
     * the same 0 the schema defaults to. NULL is never used as a numeric
     * sentinel in this system.
     *
     * @param string $table Table receiving the column.
     * @param string $fk    FK column name.
     * @return string The ALTER TABLE statement.
     */
    private function build_alter_add_fk(string $table, string $fk): string {
        $index = 'idx_' . $table . '_' . $fk;
        if (strlen($index) > 64) {
            $index = 'idx_' . substr(md5($table . '_' . $fk), 0, 16);
        }
        return 'ALTER TABLE `' . $table . '`'
             . ' ADD COLUMN `' . $fk . '` INT DEFAULT 0,'
             . ' ADD INDEX `' . $index . '` (`' . $fk . '`);';
    }

    /**
     * Build CREATE TABLE SQL for a junction table.
     *
     * Columns use singularised names ({singular_a}_id, {singular_b}_id).
     * Link columns default to 0, matching the soft-FK convention: NULL is
     * never used as a numeric sentinel. Junction rows are only ever
     * written by the runtime with both FKs explicit, so the default is
     * never actually stored.
     *   - one to one (with bridging): UNIQUE on each FK (one link per record)
     *   - many to many: composite UNIQUE on the pair (no duplicate links)
     *
     * @param string $parent     Parent table name.
     * @param string $child      Child table name.
     * @param string $singular_a Singular name of the parent.
     * @param string $singular_b Singular name of the child.
     * @param string $type       Relation type key ('one to one' | 'many to many').
     * @return string The CREATE TABLE statement.
     */
    private function build_junction_sql(string $parent, string $child, string $singular_a, string $singular_b, string $type): string {
        $junction = 'associated_' . $parent . '_and_' . $child;
        $col_a = $singular_a . '_id';
        $col_b = $singular_b . '_id';

        if ($type === 'many to many') {
            // many to many — composite unique on the pair
            $uniques = '  UNIQUE KEY `uq_' . $junction . '_pair` (`' . $col_a . '`, `' . $col_b . '`)';
        } else {
            // one to one (with bridging) — unique on each side
            $uniques = '  UNIQUE KEY `uq_' . $junction . '_' . $col_a . '` (`' . $col_a . '`),' . PHP_EOL
                     . '  UNIQUE KEY `uq_' . $junction . '_' . $col_b . '` (`' . $col_b . '`)';
        }

        return 'CREATE TABLE `' . $junction . '` (' . PHP_EOL
             . '  `id` INT PRIMARY KEY AUTO_INCREMENT,' . PHP_EOL
             . '  `' . $col_a . '` INT DEFAULT 0,' . PHP_EOL
             . '  `' . $col_b . '` INT DEFAULT 0,' . PHP_EOL
             . $uniques . PHP_EOL
             . ');';
    }

    /**
     * Fallback singular derivation when the wizard lacks plural_maker output
     * (defensive — the controller always sets singular_a/singular_b).
     *
     * @param string $table Table name.
     * @return string Best-effort singular.
     */
    private function fallback_singular(string $table): string {
        if (str_ends_with($table, 'ies')) {
            return substr($table, 0, -3) . 'y';
        }
        if (str_ends_with($table, 's')) {
            return substr($table, 0, -1);
        }
        return $table;
    }

}
