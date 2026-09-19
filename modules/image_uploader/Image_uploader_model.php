<?php
/**
 * Image_uploader_model — Data layer for the image uploader RUNTIME.
 *
 * Serves the runtime needs of modules that Flo's Image Uploader Builder
 * has already equipped with a single-image feature:
 *
 *   - read + validate the per-module settings JSON (the contract between
 *     the builder and the runtime — see get_uploader_settings());
 *   - read/update the target module's picture column (filename only —
 *     paths and URLs are always constructed server-side, never stored);
 *   - answer whether a record exists, via the null return from
 *     get_picture_file_name(), so callers never write to a missing row.
 *
 * Generation-time logic — choosing eligible modules, writing the settings
 * JSON, running the idempotent ALTER — is NOT part of this file. That work
 * happens once, at generation time, in
 * modules/trongate_control/image_uploader_builder/. This model only READS
 * the settings file that the builder wrote.
 *
 * Settings JSON shape (modules/image_uploader/settings/{module}.json):
 *   module                  — the target module name (== its table name)
 *   column                  — the picture column on the target table
 *   destination             — module-relative storage dir (default 'uploads')
 *   max_size                — upload size cap in KB (validation rule)
 *   max_width/max_height    — source dimension caps (validation rules)
 *   resize_max_width/height — downscale-to dimensions (image->upload)
 *   thumbnails              — bool; whether a thumbnail is generated
 *   thumbnail_max_width     — thumbnail max width in px
 *   thumbnail_max_height    — thumbnail max height in px
 *
 * Storage layout (per record id $update_id):
 *   modules/{module}/{destination}/{update_id}/{filename}          ← main
 *   modules/{module}/{destination}/{update_id}/thumbs/{filename}   ← thumb
 *
 * Trust boundary — identifiers in SQL:
 *   Queries concatenate the table name (module) and column name from the
 *   settings file, because MySQL cannot bind identifiers. This is safe
 *   ONLY because those values are validated against `^[a-z0-9_]+$` on
 *   every read (see get_uploader_settings()) — the runtime never accepts
 *   identifiers from the client, and the settings file itself is written
 *   exclusively by the builder after identical validation.
 */
class Image_uploader_model extends Model {

    /**
     * Path to the runtime module's settings directory, relative to APPPATH.
     *
     * Settings live inside the app's own image_uploader runtime module so
     * they are versioned alongside the generated application code and
     * survive independently of the builder (MRB convention).
     */
    private const SETTINGS_DIR = 'modules/image_uploader/settings';

    /**
     * Read and validate the uploader settings for a given module.
     *
     * Returns the decoded associative array when a settings file exists
     * and every field passes schema + charset validation; null otherwise
     * (no uploader configured, or a corrupt/hostile file — both are
     * treated identically by callers: render nothing / refuse the write).
     *
     * @param string $module The module name (already URL-decoded, lower).
     * @return array|null The validated settings, or null.
     */
    public function get_uploader_settings(string $module): ?array {
        $module = strtolower($module);

        if (preg_match('/^[a-z0-9_]+$/', $module) !== 1) {
            return null;
        }

        $settings_path = APPPATH . self::SETTINGS_DIR . '/' . $module . '.json';

        if (!is_file($settings_path)) {
            return null;
        }

        $json = file_get_contents($settings_path);

        if ($json === false) {
            return null;
        }

        $settings = json_decode($json, true);

        if (!is_array($settings)) {
            return null;
        }

        return $this->validate_settings($settings, $module);
    }

    /**
     * Validate a decoded settings array against the schema contract.
     *
     * Every identifier (module, column, destination) must match the strict
     * `^[a-z0-9_]+$` charset — no slashes, dots or dashes — so that table,
     * column and directory names are safe to concatenate into SQL and
     * filesystem paths. Numeric settings must be non-negative integers.
     * The decoded module field must equal the filename-derived $module.
     *
     * @param array  $settings The decoded settings array.
     * @param string $module   The module name the file was read for.
     * @return array|null The validated settings, or null on any failure.
     */
    private function validate_settings(array $settings, string $module): ?array {
        $string_keys = ['module', 'column', 'destination'];
        $int_keys = [
            'max_size', 'max_width', 'max_height',
            'resize_max_width', 'resize_max_height',
            'thumbnail_max_width', 'thumbnail_max_height'
        ];

        foreach ($string_keys as $key) {
            $value = $settings[$key] ?? '';

            if (!is_string($value) || preg_match('/^[a-z0-9_]+$/', $value) !== 1) {
                return null;
            }
        }

        if ($settings['module'] !== $module) {
            return null;
        }

        foreach ($int_keys as $key) {
            $value = $settings[$key] ?? null;

            if (!is_int($value) || $value < 0) {
                return null;
            }
        }

        if (!array_key_exists('thumbnails', $settings) || !is_bool($settings['thumbnails'])) {
            return null;
        }

        return $settings;
    }

    /**
     * Fetch the current picture file name for a record.
     *
     * @param string $module    The module/table name (validated upstream).
     * @param string $column    The picture column (validated upstream).
     * @param int    $update_id The record id.
     * @return string|null The stored file name ('' = no picture), or null
     *                     when the record itself does not exist.
     */
    public function get_picture_file_name(string $module, string $column, int $update_id): ?string {
        if ($update_id === 0) {
            return null;
        }

        $sql = 'SELECT `' . $column . '` FROM `' . $module . '` WHERE id = :update_id';
        $rows = $this->db->query_bind($sql, ['update_id' => $update_id], 'object');

        if (($rows === null) || (count($rows) === 0)) {
            return null;
        }

        return (string) ($rows[0]->{$column} ?? '');
    }

    /**
     * Set (or clear) the picture file name on a record.
     *
     * @param string $module    The module/table name (validated upstream).
     * @param string $column    The picture column (validated upstream).
     * @param int    $update_id The record id.
     * @param string $file_name The file name to store ('' clears).
     * @return void
     */
    public function set_picture_file_name(string $module, string $column, int $update_id, string $file_name): void {
        if ($update_id === 0) {
            return;
        }

        $sql = 'UPDATE `' . $module . '` SET `' . $column . '` = :file_name WHERE id = :update_id';
        $this->db->query_bind($sql, ['file_name' => $file_name, 'update_id' => $update_id]);
    }
}
