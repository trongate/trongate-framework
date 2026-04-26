<?php
class Flo extends Trongate {

    public $api_base_url = 'https://trongate.io/';

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url('flo');

        if (strtolower(ENV) !== 'dev') {
            http_response_code(403);
            echo '403 Forbidden - Endpoint disabled since not in dev mode';
            die();
        }
    }

    /**
     * Renders the FLO trigger element to the view.
     *
     * This method prepares the module data and invokes the view file
     * responsible for displaying the FLO button/interface trigger.
     *
     * @return void
     */
    public function draw_flow_trigger(): void {
        $data['api_base_url'] = $this->api_base_url;
        $data['view_module'] = 'trongate_control/flo';
        $this->view('flo_trigger', $data);
    }

    /**
     * Outputs the "Open Code Generator" trigger element.
     *
     * If a custom HTML string is provided via the $html parameter, it will be used.
     * Otherwise, the default view 'flo_trigger' will be rendered and returned as a string.
     *
     * @param string|null $html Optional HTML string to use as the trigger element.
     * @return void Outputs the trigger element directly.
     */
    public function draw_open_flo(?string $html = null): void {
        $data['api_base_url'] = $this->api_base_url;
        $trigger_el = (isset($html)) ? $html : $this->view('flo_trigger', $data, true);
        echo $trigger_el;
    }

}