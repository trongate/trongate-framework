<?php
/**
 * Evo — Flo's generic controller (shared utilities & menus).
 *
 * This child module holds ONLY commonly-used, generic functionality for the
 * Flo system. Feature-specific concerns live in dedicated child modules:
 *
 *   - module_builder            — the 'Create Module' wizard (formerly part of Evo)
 *   - site_builder              — the code-generation engine
 *   - module_relations_builder  — the 'Module Relations' wizard
 *
 * Evo responsibilities:
 *   - Flo menu views: home(), module_manager()
 *   - reset(): clears the shared Flo wizard session ($_SESSION['evo_wizard'])
 *   - render_error(): reusable error view for sibling child modules
 *   - render_disabled_response(): dev-mode guard response
 *
 * Dev-mode only: the constructor renders a 403 'disabled' response outside
 * dev environments.
 *
 * @package Trongate_control
 * @author David Connelly
 */
class Evo extends Trongate {

    /**
     * Base URL of the Trongate API (used for troubleshooting links).
     *
     * @var string
     */
    public $api_base_url = 'https://trongate.io/';

    /**
     * Constructor — dev-mode guard for the whole Flo system.
     *
     * @param string|null $module_name The module name (set by the framework).
     */
    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            $this->render_disabled_response();
            die();
        }
    }

    /**
     * Renders the Flo home menu.
     *
     * Loaded inside the Flo shell (flo.php) as the default main content.
     *
     * @return void
     */
    public function home(): void {
        $this->view('home');
    }

    /**
     * Renders the Module Manager menu.
     *
     * Lists the Flo feature entry points (e.g. 'Create Module').
     *
     * @return void
     */
    public function module_manager(): void {
        $this->view('module_manager');
    }

    /**
     * Reset the Flo wizard — clear the shared wizard session and return to
     * the main menu.
     *
     * 'Reset' resets the entire Flo application, not just one feature. All
     * Flo features share the single $_SESSION['evo_wizard'] key.
     *
     * @return void
     */
    public function reset(): void {
        unset($_SESSION['evo_wizard']);
        $this->view('home');
    }

    /**
     * Render an error message view.
     *
     * Public so sibling child modules (e.g. module_builder, module_relations_builder)
     * can reuse it. block_url() prevents direct invocation via the URL; the
     * message argument is optional purely so URL invocation reaches the
     * block_url() check (the dispatcher calls methods without arguments).
     *
     * @param string $message The error message to display.
     * @return string The rendered error element HTML.
     */
    public function render_error(string $message = ''): string {
        block_url('trongate_control-evo/render_error');
        $data['view_module'] = 'trongate_control/evo';
        $data['message'] = $message;
        return $this->view('error_element', $data, true);
    }

    /**
     * Render the generic 'details review' iframe shell.
     *
     * Public so sibling child modules (module_builder, module_relations_builder, ...)
     * can reuse it for their 'view & confirm details' overlays. block_url()
     * prevents direct invocation via the URL; the data argument is optional
     * purely so URL invocation reaches the block_url() check (the dispatcher
     * calls methods without arguments).
     *
     * @param array $data View data: view_content, local_storage_items,
     *                    after_close_url, after_close_width,
     *                    after_close_height, page_title (optional).
     * @return void
     */
    public function render_details_iframe(array $data = []): void {
        block_url('trongate_control-evo/render_details_iframe');
        $data['view_module'] = 'trongate_control/evo';
        $this->view('module_details_iframe', $data);
    }

    /**
     * Render a 'generation failed' error view with optional 'Learn More' link.
     *
     * Public so sibling child modules (module_builder, module_relations_builder, ...)
     * can reuse it when their code-generation steps fail. block_url()
     * prevents direct invocation via the URL.
     *
     * @param string $message       The error message to display.
     * @param string $more_info_url Optional troubleshooting URL.
     * @return void
     */
    public function render_generation_error(string $message = '', string $more_info_url = ''): void {
        block_url('trongate_control-evo/render_generation_error');
        $data['view_module'] = 'trongate_control/evo';
        $data['message'] = $message;
        $data['more_info_url'] = $more_info_url;
        $this->view('generation_error', $data);
    }

    /**
     * Render a disabled response when the module is accessed outside dev mode.
     *
     * @return void
     */
    public function render_disabled_response(): void {
        http_response_code(403);
        $data['view_module'] = 'trongate_control/evo';
        $this->view('disabled', $data);
    }

}
