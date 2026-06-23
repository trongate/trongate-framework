<?php
/**
 * Query_builder — Child module of trongate_control
 *
 * Visual SQL query builder for constructing SELECT queries with JOINs.
 * Users drag database tables onto a canvas, click columns to define
 * join relationships, and select join types via a visual menu.
 *
 * Originally hosted on the mothership; migrated local for zero
 * cross-origin dependency.
 */
class Query_builder extends Trongate {

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            $this->module('trongate_control-evo');
            $this->evo->render_disabled_response();
            die();
        }
    }

    /**
     * Entry point: serve the Query Builder UI with live table data.
     */
    public function home(): void {
        $tables = $this->get_tables();
        $data['tables_json'] = json_encode($tables);
        $data['view_module'] = 'trongate_control/query_builder';
        $this->view('query_builder', $data);
    }

    // ─── Private Helpers ───────────────────────────────────────

    /**
     * Fetch all database tables and their columns via information_schema.
     *
     * @return array  Each element: ['id' => 'table_name', 'columns' => ['col1', 'col2', ...]]
     */
    private function get_tables(): array {
        $rows = $this->db->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME",
            'object'
        );

        $tables = [];

        foreach ($rows as $row) {
            $table_name = $row->TABLE_NAME;

            $col_rows = $this->db->query_bind(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table
                 ORDER BY ORDINAL_POSITION",
                ['table' => $table_name],
                'object'
            );

            $columns = [];
            foreach ($col_rows as $col) {
                $columns[] = $col->COLUMN_NAME;
            }

            $tables[] = [
                'id' => $table_name,
                'columns' => $columns
            ];
        }

        return $tables;
    }
}
