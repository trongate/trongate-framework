<?php

/**
 * Set flash data to be used for displaying messages across requests.
 *
 * @param string $msg The message to be stored as flash data.
 */
function set_flashdata($msg) {
    $_SESSION['flashdata'] = $msg;
}


/**
 * Display flash data message and optionally wrap it with HTML.
 *
 * @param string|null $opening_html Optional. Opening HTML tags to wrap around the flash data message.
 * @param string|null $closing_html Optional. Closing HTML tags to wrap around the flash data message.
 */
function flashdata($opening_html = null, $closing_html = null) {

    if (isset($_SESSION['flashdata'])) {

        if (!isset($opening_html)) {
            if (defined('FLASHDATA_OPEN') && defined('FLASHDATA_CLOSE')) {
                $opening_html = FLASHDATA_OPEN;
                $closing_html = FLASHDATA_CLOSE;
            } else {
                $opening_html = '<p style="color: green;">';
                $closing_html = '</p>';
            }
        }

        echo $opening_html . $_SESSION['flashdata'] . $closing_html;
        unset($_SESSION['flashdata']);
    }
}
