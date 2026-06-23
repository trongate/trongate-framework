<?php
class Flo extends Trongate {

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);
        block_url('flo');

        if (strtolower(ENV) !== 'dev') {
            $this->module('trongate_control-evo');
            $this->evo->render_disabled_response();
            die();
        }
    }

    public function home() {
        $this->view('flo');
    }

    /**
     * Renders the FLO trigger element to the view.
     *
     * This method prepares the module data and invokes the view file
     * responsible for displaying the FLO button/interface trigger.
     *
     * @return string
     */
    public function draw_flow_trigger(): string {
        $data = [
            'view_module' => 'trongate_control/flo'
        ];

        return $this->view('flo_trigger', $data, true);
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
        $trigger_el = (isset($html)) ? $html : $this->view('flo_trigger', [], true);
        echo $trigger_el;
    }

}
