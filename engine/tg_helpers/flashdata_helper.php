<?php
function set_flashdata($msg) {
    $_SESSION['flashdata'] = $msg;
}

function flashdata($opening_html=null, $closing_html=null) {

    if (isset($_SESSION['flashdata'])) {

        if(!isset($opening_html)) {
            if (defined('FLASHDATA_OPEN') && defined('FLASHDATA_CLOSE')) {
                $opening_html = FLASHDATA_OPEN;
                $closing_html = FLASHDATA_CLOSE;
            } else {
                $opening_html = '<p style="color: green;">';
                $closing_html = '</p>';
            }
        }
        
        echo $opening_html.$_SESSION['flashdata'].$closing_html;
        unset($_SESSION['flashdata']);
    }
}