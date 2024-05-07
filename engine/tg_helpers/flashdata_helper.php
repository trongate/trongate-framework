<?php
/**
 * Sets a flash data message into the session.
 * Flash data is typically used for one-time messages (e.g., form submission success/failure).
 *
 * @param string $msg The message to store as flash data.
 * @return void
 */
function set_flashdata(string $msg): void {
    $_SESSION['flashdata'] = $msg;
}

/**
 * Outputs and clears flash data from the session wrapped with optional HTML tags.
 * If no HTML tags are specified and constants FLASHDATA_OPEN and FLASHDATA_CLOSE are defined,
 * it uses these constants as the HTML wrapper. Otherwise, it defaults to a simple paragraph tag with green text.
 *
 * @param string|null $opening_html Optional HTML opening tag to wrap the flash message. Defaults to null.
 * @param string|null $closing_html Optional HTML closing tag to wrap the flash message. Defaults to null.
 * @return void
 */
function flashdata(?string $opening_html = null, ?string $closing_html = null): void {

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