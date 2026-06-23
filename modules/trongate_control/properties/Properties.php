<?php
class Properties extends Trongate {

    public function __construct(?string $module_name = null) {
        parent::__construct($module_name);

        if (strtolower(ENV) !== 'dev') {
            $this->module('trongate_control-evo');
            $this->evo->render_disabled_response();
            die();
        }
    }

    /**
     * Return field info for all property types.
     *
     * This endpoint serves the Properties Builder with available property
     * field types, validation rules, checkboxes, and naming suggestions.
     *
     * @return void (outputs JSON)
     */
    public function get_fields_info(): void {
        $json = file_get_contents(APPPATH . 'modules/trongate_control/properties/data/fields_info.json');
        echo $json;
    }

    /**
     * Return available address types.
     *
     * @return void (outputs JSON array)
     */
    public function get_address_types(): void {
        $json = file_get_contents(APPPATH . 'modules/trongate_control/properties/data/address_types.json');
        echo $json;
    }

    /**
     * Return address property rows for a given address type.
     *
     * Expects JSON POST body with 'addressType' key.
     * Falls back to 'American' if no type provided.
     *
     * @return void (outputs JSON array)
     */
    public function get_address_data(): void {
        $body = json_decode(file_get_contents('php://input'), true);
        $address_type = $body['addressType'] ?? 'American';
        $filename = 'address_' . strtolower($address_type) . '.json';
        $path = APPPATH . 'modules/trongate_control/properties/data/' . $filename;

        if (file_exists($path)) {
            echo file_get_contents($path);
        } else {
            echo '[]';
        }
    }

}