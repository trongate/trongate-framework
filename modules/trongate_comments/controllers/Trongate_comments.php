<?php
/**
 * Comments controller for Trongate.
 *
 * Provides a reusable comments UI (card, modals, and AJAX handlers) for any
 * table record, plus permission checks that leverage Trongate tokens/security.
 *
 * Responsibilities:
 * - Render a comments card for a target table/update_id.
 * - Create, update, fetch, and delete comments via AJAX endpoints.
 * - Enforce permissions for fetch, upsert, and delete scenarios.
 */
class Trongate_comments extends Trongate {
    /** @var string Username label for system-authored comments (user_id = 0). */
    private string $zero_id_username = 'System';

    /** @var string Fallback username label when no matching administrator is found. */
    private string $not_found_username = 'Unknown User';

    /**
     * Renders the comments card UI for a database table record.
     *
     * Prepares data for the `comments_card.php` view, which renders a comments section
     * (table and modals for adding, editing, deleting comments) for a specified table.
     * If `target_table` is not provided in `$data`, it defaults to the first URI segment
     * (module name). Sets `view_module` to `trongate_comments` for view resolution.
     *
     * Example usage:
     * ```php
     * $this->_draw_comments_card([
     *     'target_table' => 'articles',
     *     'update_id'    => 42
     * ]);
     * ```
     *
     * @param array<string,mixed> $data {
     *     @type string $target_table Optional. Database table name for comments. Defaults to first URI segment.
     *     @type int    $update_id    Optional. Record ID for which comments are managed.
     *     @type mixed  $...          Additional data passed to the view.
     * }
     * @return void Renders the comments card view directly.
     */
    public function _draw_comments_card(array $data): void {
        $data['target_table'] = $data['target_table'] ?? segment(1);
        $data['view_module'] = 'trongate_comments';
        $this->view('comments_card', $data);
    }

    /**
     * Create or update a comment via JSON POST.
     *
     * Expects JSON body with:
     * - targetTable (string) : table associated with the comment.
     * - updateId    (int)    : record ID associated with the comment.
     * - comment     (string) : comment text (required; min length 2).
     * - commentCode (string) : if present, updates existing comment; otherwise inserts a new one.
     *
     * Security: requires permission scenario "upsert comment" via `trongate_security`.
     *
     * Responses:
     * - 200 OK     : "Comment inserted." or "Comment updated."
     * - 400 Bad Req: Validation failure message.
     * - 403/401    : As enforced by the security layer.
     *
     * @return void Outputs status text and appropriate HTTP status, then exits on errors.
     */
    public function submit_comment(): void {
        $data = [
            'target_table' => trim(post('targetTable')),
            'update_id' => (int) trim(post('updateId')),
            'comment' => trim(post('comment'))
        ];

        $this->module('trongate_security');
        $trongate_user_obj = $this->trongate_security->_make_sure_allowed('upsert comment', $data);

        $comment_length = strlen($data['comment']);

        switch ($comment_length) {
            case 0:
                $error_msg = 'You did not enter a comment.';
                break;
            case 1:
                $error_msg = 'Your comment was too short.';
                break;
            default:
                $error_msg = '';
                break;
        }

        if ($error_msg !== '') {
            http_response_code(400);
            echo $error_msg;
            die();
        } else {
            $comment_code = post('commentCode');

            if ($comment_code === '') {
                $data['date_created'] = time();
                $data['user_id'] = (int) $trongate_user_obj->trongate_user_id;
                $data['code'] = make_rand_str(6);
                $this->model->insert($data, 'trongate_comments');
                http_response_code(200);
                echo 'Comment inserted.';
            } else {
                unset($data);
                $update_data = [
                    'user_id' => (int) $trongate_user_obj->trongate_user_id,
                    'comment' => trim(post('comment'))
                ];
                $this->model->update_where('code', $comment_code, $update_data, 'trongate_comments');
                http_response_code(200);
                echo 'Comment updated.';
            }
        }
    }

    /**
     * Delete a comment by its unique code via JSON POST.
     *
     * Expects JSON body with:
     * - commentCode (string) : the unique code of the comment to delete.
     *
     * Security: requires permission scenario "delete comment".
     * Non-admin users may only delete their own comments. Admin (user_level_id === 1) can delete any.
     *
     * Responses:
     * - 200 OK     : "Comment deleted."
     * - 400 Bad Req: "No comment code provided." or "Comment not found."
     * - 403 Forbidden: "You are not authorized to delete this comment."
     *
     * @return void Outputs status text and appropriate HTTP status, then exits on errors.
     */
    public function submit_delete_comment(): void {
        $comment_code = trim(post('commentCode'));
        if ($comment_code === '') {
            http_response_code(400);
            echo 'No comment code provided.';
            die();
        }

        $this->module('trongate_security');
        $trongate_user_obj = $this->trongate_security->_make_sure_allowed('delete comment');

        $sql = 'SELECT * FROM trongate_comments WHERE code = :code';
        $comment = $this->model->query_bind($sql, ['code' => $comment_code], 'object');
        if (empty($comment)) {
            http_response_code(400);
            echo 'Comment not found.';
            die();
        }

        $comment = $comment[0];
        $user_id = (int) ($trongate_user_obj->trongate_user_id ?? 0);
        if ($comment->user_id !== $user_id && $trongate_user_obj->user_level_id !== 1) {
            http_response_code(403);
            echo 'You are not authorized to delete this comment.';
            die();
        }

        $record_id = (int) $comment->id;
        $this->model->delete($record_id, 'trongate_comments');
        http_response_code(200);
        echo 'Comment deleted.';
    }

    /**
     * Fetch and render comments for the target table/update_id from URI.
     *
     * Reads:
     * - segment(3) => target_table
     * - segment(4) => update_id (int)
     *
     * Security: requires permission scenario "fetch comments".
     *
     * Behaviour:
     * - If no comments exist, outputs nothing (returns).
     * - Otherwise renders the `display_comments` view with:
     *   - $comments (array<object>)
     *   - $trongate_user_id (int)
     *
     * @return void Renders HTML directly or returns early when no comments.
     */
    public function fetch(): void {
        $params = [
            'target_table' => segment(3),
            'update_id' => segment(4, 'int')
        ];

        $this->module('trongate_security');
        $trongate_user_obj = $this->trongate_security->_make_sure_allowed('fetch comments', $params);

        $sql = 'SELECT
                    trongate_comments.*,
                    trongate_administrators.username 
                FROM
                    trongate_comments
                LEFT OUTER JOIN
                    trongate_administrators
                ON
                    trongate_comments.user_id = trongate_administrators.trongate_user_id  
                WHERE 
                    trongate_comments.target_table = :target_table 
                AND 
                    trongate_comments.update_id = :update_id 
                ORDER BY 
                    trongate_comments.date_created';

        $comments = $this->model->query_bind($sql, $params, 'object');

        if (empty($comments)) {
            return;
        }

        foreach ($comments as $key => $value) {
            if (!isset($value->username)) {
                $user_id = (int) ($value->user_id ?? 0);
                $comments[$key]->username = ($user_id === 0) ? $this->zero_id_username : $this->not_found_username;
            }
            $comments[$key]->username = $value->username;
        }

        $data['comments'] = $comments;
        $data['trongate_user_id'] = (int) $trongate_user_obj->trongate_user_id;
        $this->view('display_comments', $data);
    }

    /**
     * Permission dispatcher for comment scenarios.
     *
     * Supported scenarios:
     * - "upsert comment"  => write_comment_allowed()
     * - "fetch comments"  => fetch_comments_allowed()
     * - "delete comment"  => delete_comment_allowed()
     *
     * @param string               $scenario One of the supported permission scenarios.
     * @param array<string,mixed>  $params   Optional parameters passed to scenario handler.
     * @return object|null Returns the authenticated user object on success; exits on failure.
     */
    public function _make_sure_allowed(string $scenario, array $params = []): ?object {
        switch ($scenario) {
            case 'upsert comment':
                $result = $this->write_comment_allowed($scenario, $params);
                break;
            case 'fetch comments':
                $result = $this->fetch_comments_allowed($scenario, $params);
                break;
            case 'delete comment':
                $result = $this->delete_comment_allowed($scenario);
                break;
            default:
                $result = $this->fetch_comments_allowed($scenario, $params);
                break;
        }

        return $result;
    }

    /**
     * Ensure the current user may create or update a comment.
     *
     * Delegates to fetch_comments_allowed(), which enforces authentication/role rules.
     *
     * @param string               $scenario Scenario label (expected: "upsert comment").
     * @param array<string,mixed>  $params   Target table/update context.
     * @return object|null The authenticated user object on success; exits on failure.
     */
    private function write_comment_allowed(string $scenario, array $params): ?object {
        $trongate_user_obj = $this->fetch_comments_allowed($scenario, $params);
        return $trongate_user_obj;
    }

    /**
     * Ensure the current user may delete a comment.
     *
     * Admins (user_level_id === 1) are allowed. Non-admin users are rejected here;
     * finer-grained ownership checks occur in submit_delete_comment().
     *
     * @param string $scenario Scenario label (expected: "delete comment").
     * @return object|null The authenticated user object on success; exits on failure.
     */
    private function delete_comment_allowed(string $scenario): ?object {
        $this->module('trongate_tokens');
        $trongate_user_obj = $this->trongate_tokens->_get_user_obj();
        $user_level_id = (int) ($trongate_user_obj->user_level_id ?? 0);

        if ($user_level_id === 1) { // 'admin' user level.
            return $trongate_user_obj;
        } else {
            $this->not_allowed($scenario);
        }
    }

    /**
     * Ensure the current user may fetch (and by delegation, upsert) comments.
     *
     * Validates that $params['target_table'] exists, then requires an authenticated
     * admin (user_level_id === 1). Non-admins are rejected.
     *
     * @param string               $scenario Scenario label ("fetch comments" or "upsert comment").
     * @param array<string,mixed>  $params   Must contain 'target_table'.
     * @return object|null The authenticated user object on success; exits on failure.
     */
    private function fetch_comments_allowed(string $scenario, array $params): ?object {
        $target_table = $params['target_table'] ?? '';
        $target_table_exists = $this->model->table_exists($target_table);
        if ($target_table_exists !== true) {
            http_response_code(400);
            die();
        }

        $this->module('trongate_tokens');
        $trongate_user_obj = $this->trongate_tokens->_get_user_obj();
        $user_level_id = (int) ($trongate_user_obj->user_level_id ?? 0);

        if ($user_level_id === 1) { // 'admin' user level.
            return $trongate_user_obj;
        } else {
            $this->not_allowed($scenario);
        }
    }

    /**
     * Emit a permission error for the given scenario and terminate.
     *
     * Scenarios:
     * - "upsert comment"  => echoes "error for upsert comment"
     * - "delete comment"  => echoes "error for delete comment"
     * - other             => sets HTTP 401
     *
     * @param string $scenario Scenario label.
     * @return never Terminates script execution.
     */
    private function not_allowed(string $scenario): never {
        if ($scenario === 'upsert comment') {
            echo 'error for upsert comment';
        } elseif ($scenario === 'delete comment') {
            echo 'error for delete comment';
        } else {
            http_response_code(401);
        }

        die();
    }
}