<?php

/**
 * Sets flash data message to be displayed.
 *
 * @param string $msg The message to be set as flash data.
 */
function set_flashdata($msg) {
    $_SESSION['flashdata'] = $msg;
}

/**
 * Displays flash data message if set.
 *
 * @param string|null $opening_html Optional opening HTML tag for the flash message.
 * @param string|null $closing_html Optional closing HTML tag for the flash message.
 */
function flashdata($opening_html = null, $closing_html = null) {

    // Check if flash data is set
    if (isset($_SESSION['flashdata'])) {

        // Set default opening and closing HTML tags if not provided
        if (!isset($opening_html)) {
            if (defined('FLASHDATA_OPEN') && defined('FLASHDATA_CLOSE')) {
                $opening_html = FLASHDATA_OPEN;
                $closing_html = FLASHDATA_CLOSE;
            } else {
                $opening_html = '<p style="color: green;">';
                $closing_html = '</p>';
            }
        }

        // Display the flash data message
        echo $opening_html . $_SESSION['flashdata'] . $closing_html;

        // Unset flash data after displaying
        unset($_SESSION['flashdata']);
    }
}