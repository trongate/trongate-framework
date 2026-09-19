<?php
/**
 * Image_uploader_builder_model - Data layer for Flo's image uploader wizard.
 *
 * CRUD eligibility for the module chooser, preflight guards, the
 * idempotent ALTER TABLE, the settings JSON write, and the template-tag
 * decode used by the controller's code injection.
 *
 * Generation-time only, like its controller. The runtime counterpart
 * lives in the top-level image_uploader module; this model decides WHAT
 * gets created (schema + settings) against the exact contract that
 * image_uploader's Image_uploader_model validates on every read. Code
 * injection itself lives in the controller (models cannot call view()).
 */
class Image_uploader_builder_model extends Model {

    /**
     * The runtime module's settings directory (relative to APPPATH).
     *
     * MUST match the runtime's own SETTINGS_DIR
     * (modules/image_uploader/Image_uploader_model.php) — the builder
     * writes the contract the runtime reads.
     */
    private const SETTINGS_DIR = 'modules/image_uploader/settings';

    /**
     * Framework/system module folders never offered as uploader targets.
     */
    private const SYSTEM_MODULES = [
        'db', 'endpoint_listener', 'file', 'flashdata', 'form', 'image',
        'image_uploader', 'language', 'login', 'module_relations',
        'pagination', 'string_service', 'temp', 'templates', 'url',
        'utilities', 'validation', 'welcome'
    ];

    // ============================================
    // Code injection — template decode
    // ============================================

    /**
     * Decode entity-escaped PHP tags in rendered code templates.
     *
     * The site_builder pattern (shared with module_relations_builder):
     * templates write &lt;?php / &lt;?= as entities so they are not executed
     * when the template is rendered; this restores them for output into
     * the target file.
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
    // Module chooser (CRUD preflight)
    // ============================================

    /**
     * List candidate modules with their CRUD preflight status.
     *
     * A module is READY for an image uploader when ALL of the following
     * hold:
     *   - a controller file exists (modules/{module}/{Module}.php)
     *   - a model file exists ({Module}_model.php)
     *   - a show view exists (views/show.php)
     *   - a delete flow exists (submit_delete method in the controller)
     * The module folder must also have a matching database table — checked
     * separately at run_gen, against the table named in the settings.
     *
     * Modules failing any check are returned with the specific reason(s);
     * non-ready modules cannot proceed in the wizard.
     *
     * @return array<int, array> Rows: module, label, ready, reasons[].
     */
    public function get_crud_modules(): array {
        $modules_dir = APPPATH . 'modules';
        $entries = scandir($modules_dir);

        $rows = [];

        foreach ($entries as $entry) {
            if (($entry === '.') || ($entry === '..')) {
                continue;
            }
            if (!is_dir($modules_dir . '/' . $entry)) {
                continue;
            }
            if (str_starts_with($entry, 'trongate_')) {
                continue; // framework / Flo internals
            }
            if (in_array($entry, self::SYSTEM_MODULES, true)) {
                continue; // framework helper modules / runtimes
            }
            if (preg_match('/^[a-z0-9_]+$/', $entry) !== 1) {
                continue; // the charset rule applies to every candidate
            }

            $reasons = $this->crud_shortfalls($entry);

            $rows[] = [
                'module' => $entry,
                'label' => ucwords(str_replace('_', ' ', $entry)),
                'ready' => (count($reasons) === 0),
                'reasons' => $reasons
            ];
        }

        // Ready modules first, then blocked ones — alphabetical within each.
        usort($rows, function ($a, $b) {
            if ($a['ready'] !== $b['ready']) {
                return $a['ready'] ? -1 : 1;
            }
            return strcmp($a['module'], $b['module']);
        });

        return $rows;
    }

    /**
     * Resolve a posted selection to a ready module name.
     *
     * Never trusts the client: the module must appear in the ready list
     * (recomputed server-side) or null is returned.
     *
     * @param string $selected The posted module name.
     * @return string|null The canonical module name, or null.
     */
    public function get_eligible_module(string $selected): ?string {
        $selected = strtolower(trim($selected));

        if (preg_match('/^[a-z0-9_]+$/', $selected) !== 1) {
            return null;
        }

        foreach ($this->get_crud_modules() as $row) {
            if (($row['module'] === $selected) && $row['ready']) {
                return $selected;
            }
        }

        return null;
    }

    // ─── Private eligibility helpers ─────────────────────────

    /**
     * CRUD shortfalls for a single module folder.
     *
     * @param string $module Module folder name.
     * @return array<string> Human-readable reason strings (empty = ready).
     */
    private function crud_shortfalls(string $module): array {
        $reasons = [];
        $dir = APPPATH . 'modules/' . $module;
        $class = ucfirst($module);

        if (!is_file($dir . '/' . $class . '.php')) {
            $reasons[] = 'no controller file (' . $class . '.php)';
        }

        if (!is_file($dir . '/' . $class . '_model.php')) {
            $reasons[] = 'no model file (' . $class . '_model.php)';
        }

        if (!is_file($dir . '/views/show.php')) {
            $reasons[] = 'no show view (views/show.php)';
        }

        if ($this->has_delete_flow($dir . '/' . $class . '.php') === false) {
            $reasons[] = 'no delete flow (submit_delete method)';
        }

        return $reasons;
    }

    /**
     * Whether a controller file contains a submit_delete method.
     *
     * @param string $controller_path Absolute path to the controller file.
     * @return bool True when a submit_delete method exists.
     */
    private function has_delete_flow(string $controller_path): bool {
        if (!is_file($controller_path)) {
            return false;
        }
        $contents = (string) file_get_contents($controller_path);
        return (strpos($contents, 'function submit_delete') !== false);
    }

    // ============================================
    // Marker (settings file = 'already has an uploader')
    // ============================================

    /**
     * Whether the module already has an uploader settings file.
     *
     * The settings file doubles as the duplicate-feature marker: marker
     * present → friendly message on re-run, nothing changed.
     *
     * @param string $module The module name (validated upstream).
     * @return bool True when a settings file exists for the module.
     */
    public function uploader_exists(string $module): bool {
        if (preg_match('/^[a-z0-9_]+$/', $module) !== 1) {
            return false;
        }
        return is_file($this->settings_path($module));
    }

    /**
     * Delete the settings file for a module (if present).
     *
     * Used by the controller's run_gen() catch block: a settings file must
     * never survive a failed generation, or a retry would be blocked by
     * the duplicate-feature guard (the settings file IS the marker).
     *
     * @param string $module The module name.
     * @return void
     */
    public function delete_settings_file(string $module): void {
        if (preg_match('/^[a-z0-9_]+$/', $module) !== 1) {
            return;
        }
        $path = $this->settings_path($module);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // ============================================
    // Preflight guards (fail loudly, write nothing)
    // ============================================

    /**
     * Run the complete generation pre-flight.
     *
     * Every check happens BEFORE any mutation (no ALTER, no settings
     * write). Any failure throws \Exception with a clean, actionable
     * message. Duplicate-feature detection is deliberately not here: the
     * controller reports it with a friendly notice before generating.
     *
     * @param array $wizard The wizard session array.
     * @return void
     * @throws \Exception On the first failed check.
     */
    public function guard_uploader_ready(array $wizard): void {
        $module = $wizard['module'] ?? '';
        $table = $wizard['table'] ?? '';
        $column = $wizard['column'] ?? '';
        $destination = $wizard['destination'] ?? '';

        // Identifier charset (same rule the runtime enforces on read).
        foreach (['module' => $module, 'table' => $table, 'column' => $column, 'destination' => $destination] as $key => $value) {
            if (preg_match('/^[a-z0-9_]+$/', (string) $value) !== 1) {
                throw new \Exception("The {$key} name '{$value}' may contain lowercase letters, numbers, and underscores only.");
            }
        }

        // Module must still exist and be a real app module.
        $module_dir = APPPATH . 'modules/' . $module;
        if (!is_dir($module_dir)) {
            throw new \Exception("The '{$module}' module folder no longer exists. Start again from the Module Manager.");
        }

        // Target table must exist before any ALTER is attempted.
        if (!$this->table_exists($table)) {
            throw new \Exception("The table '{$table}' does not exist in the database. Check the table name (it defaults to the module name but can differ).");
        }

        // GD is a hard dependency of the framework Image module.
        if (!extension_loaded('gd')) {
            throw new \Exception('The GD image extension is not loaded. The framework Image module needs GD to process uploads — enable it in php.ini and restart the web server.');
        }

        // Settings dir must exist and be writable (the file we write).
        $this->ensure_settings_dir_writable();

        // Target module folder + destination root must be writable — the
        // runtime will create per-record upload folders under it.
        $this->ensure_destination_writable($module, $destination);
    }

    /**
     * Ensure the runtime settings directory exists and is writable.
     *
     * @return void
     * @throws \Exception When the directory cannot be created or written.
     */
    private function ensure_settings_dir_writable(): void {
        $dir = APPPATH . self::SETTINGS_DIR;

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new \Exception('The image_uploader settings directory could not be created: ' . $dir);
            }
            @chmod($dir, 0777);
        }

        if (!is_writable($dir)) {
            throw new \Exception('The image_uploader settings directory is not writable: ' . $dir . ' (run: chmod 0777 ' . $dir . ')');
        }
    }

    /**
     * Ensure the target module's destination root can be created/written.
     *
     * The root folder is created at generation time (empty); the runtime
     * creates per-record subfolders under it on each upload.
     *
     * @param string $module      Module name (validated upstream).
     * @param string $destination Destination root (validated upstream).
     * @return void
     * @throws \Exception When the folder cannot be created.
     */
    private function ensure_destination_writable(string $module, string $destination): void {
        $dir = APPPATH . 'modules/' . $module . '/' . $destination;

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new \Exception('The destination folder could not be created: ' . $dir . ' — make sure the ' . $module . ' module folder is writable by the web server (run: chmod 0777 ' . APPPATH . 'modules/' . $module . ')');
            }
            @chmod($dir, 0777);
        }

        if (!is_writable($dir)) {
            throw new \Exception('The destination folder is not writable: ' . $dir . ' (run: chmod 0777 ' . $dir . ')');
        }
    }

    // ============================================
    // Idempotent ALTER TABLE
    // ============================================

    /**
     * Apply the schema change idempotently.
     *
     * information_schema decides:
     *   - column absent            → ADD COLUMN
     *   - present + compatible     → skip, clear message (nothing changed)
     *   - present + incompatible   → fail loudly, never silently alter
     *
     * @param array $wizard The wizard session array.
     * @return string A human-readable outcome message.
     * @throws \Exception On an incompatible existing column.
     */
    public function apply_column(array $wizard): string {
        $table = $wizard['table'] ?? '';
        $column = $wizard['column'] ?? '';

        $state = $this->get_column_state($table, $column);

        if ($state === 'compatible') {
            return 'Column `' . $column . '` already exists on `' . $table . '` with a compatible definition (VARCHAR(255) NOT NULL DEFAULT \'\') — no schema change was needed.';
        }

        if ($state === 'incompatible') {
            $found = $this->describe_column($table, $column);
            throw new \Exception("Column '{$column}' already exists on the {$table} table with an incompatible definition: {$found}. Expected VARCHAR(255) NOT NULL DEFAULT '' (empty string = no picture). Nothing was changed — drop or rename the existing column, or choose a different column name in the wizard.");
        }

        // Absent → add.
        $sql = $this->build_alter_sql($wizard);
        $this->db->query($sql);

        return 'Column `' . $column . '` was added to `' . $table . '` (VARCHAR(255) NOT NULL DEFAULT \'\').';
    }

    /**
     * Build the ALTER TABLE statement that adds the picture column.
     *
     * Only reached once get_column_state() has confirmed the column is
     * absent, so the statement is never issued against an existing column.
     *
     * @param array $wizard The wizard session array.
     * @return string The single ALTER TABLE statement.
     */
    public function build_alter_sql(array $wizard): string {
        $table = $wizard['table'] ?? '';
        $column = $wizard['column'] ?? '';
        return 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` VARCHAR(255) NOT NULL DEFAULT \'\';';
    }

    /**
     * Column state against the runtime contract
     * (VARCHAR(255) NOT NULL DEFAULT '').
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return string 'absent' | 'compatible' | 'incompatible'.
     */
    private function get_column_state(string $table, string $column): string {
        if (!$this->table_exists($table)) {
            return 'absent';
        }

        $rows = $this->db->query_bind(
            "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column",
            ['table' => $table, 'column' => $column],
            'object'
        );

        if (($rows === null) || (count($rows) === 0)) {
            return 'absent';
        }

        $col = $rows[0];
        $type_ok = (strtoupper((string) $col->COLUMN_TYPE) === 'VARCHAR(255)');
        $nullable_ok = (strtoupper((string) $col->IS_NULLABLE) === 'NO');
        $default_ok = ($col->COLUMN_DEFAULT === ''); // empty string = no picture

        return ($type_ok && $nullable_ok && $default_ok) ? 'compatible' : 'incompatible';
    }

    /**
     * Human-readable existing definition of a column (for error messages).
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return string e.g. "INT NOT NULL" or "VARCHAR(255) NULL DEFAULT NULL".
     */
    private function describe_column(string $table, string $column): string {
        $rows = $this->db->query_bind(
            "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column",
            ['table' => $table, 'column' => $column],
            'object'
        );

        if (($rows === null) || (count($rows) === 0)) {
            return 'column not found';
        }

        $col = $rows[0];
        $default = ($col->COLUMN_DEFAULT === null) ? 'DEFAULT NULL' : "DEFAULT '" . $col->COLUMN_DEFAULT . "'";
        return strtoupper((string) $col->COLUMN_TYPE) . ' ' . strtoupper((string) $col->IS_NULLABLE) . ' ' . $default;
    }

    /**
     * Whether a table exists in the current database.
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

    // ============================================
    // Settings JSON (the runtime contract)
    // ============================================

    /**
     * Write the uploader settings JSON for the target module.
     *
     * The file's existence doubles as the "already has an uploader"
     * marker. Written with the exact key set the runtime's
     * validate_settings() requires, in the order the other settings files
     * use.
     *
     * @param array $wizard The wizard session array.
     * @return string The settings file path written.
     * @throws \Exception When the file cannot be written.
     */
    public function write_settings_file(array $wizard): string {
        $module = $wizard['module'] ?? '';

        $settings = [
            'module' => $module,
            'column' => $wizard['column'] ?? '',
            'destination' => $wizard['destination'] ?? '',
            'max_size' => (int) ($wizard['max_size'] ?? 0),
            'max_width' => (int) ($wizard['max_width'] ?? 0),
            'max_height' => (int) ($wizard['max_height'] ?? 0),
            'resize_max_width' => (int) ($wizard['resize_max_width'] ?? 0),
            'resize_max_height' => (int) ($wizard['resize_max_height'] ?? 0),
            'thumbnails' => (bool) ($wizard['thumbnails'] ?? false),
            'thumbnail_max_width' => (int) ($wizard['thumbnail_max_width'] ?? 0),
            'thumbnail_max_height' => (int) ($wizard['thumbnail_max_height'] ?? 0)
        ];

        $this->ensure_settings_dir_writable();

        $path = $this->settings_path($module);
        $json = $this->encode_settings($settings);

        if (file_put_contents($path, $json) === false) {
            throw new \Exception('Could not write the settings file: ' . $path);
        }
        @chmod($path, 0777);

        return $path;
    }

    /**
     * Encode the settings array for the settings file.
     *
     * Pretty-printed JSON, with a trailing newline — matching the form of
     * the app's other settings files.
     *
     * @param array $settings The settings array (in contract key order).
     * @return string The encoded settings.
     */
    private function encode_settings(array $settings): string {
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return ($json === false) ? '' : $json . PHP_EOL;
    }

    /**
     * Absolute path to a module's settings file.
     *
     * @param string $module Module name.
     * @return string The settings file path.
     */
    private function settings_path(string $module): string {
        return APPPATH . self::SETTINGS_DIR . '/' . $module . '.json';
    }

}
