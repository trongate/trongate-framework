<?php
class Properties_builder extends Trongate {

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            $this->module('trongate_control-evo');
            $this->evo->render_disabled_response();
            die();
        }
    }

    /**
     * Render the Properties Builder interface.
     *
     * Entry point for the iframe overlay. The JavaScript auto-detects the 'web'
     * URL segment to enable postMessage-based communication with the parent window.
     *
     * @return void
     */
    public function web(): void {
        $data['view_module'] = 'trongate_control/properties_builder';
        $this->view('home', $data);
    }

}
