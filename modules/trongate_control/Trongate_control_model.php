<?php
/**
 * Trongate_control Model
 * Handles SQL file operations and database interactions for the Module Import Wizard.
 */
class Trongate_control_model extends Model {
    
    /**
     * Check if SQL contains potentially dangerous commands
     */
    public function check_sql_safety(string $file_contents): bool {
        $file_contents = strtolower($file_contents);
        
        $dangerous_patterns = [
            'drop ',
            'update ',
            'truncate ',
            'delete from'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (str_contains($file_contents, $pattern)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Execute SQL code
     */
    public function execute_sql(string $sql): void {
        $this->db->query($sql);
        
        http_response_code(200);
        echo 'Finished.';
    }
    
    /**
     * Delete a SQL file safely
     */
    public function delete_sql_file(string $filepath): bool {
        if (!file_exists($filepath)) {
            http_response_code(403);
            echo $filepath;
            return false;
        }
        
        if (!is_writable($filepath)) {
            http_response_code(403);
            echo $filepath;
            return false;
        }
        
        unlink($filepath);
        return true;
    }
}