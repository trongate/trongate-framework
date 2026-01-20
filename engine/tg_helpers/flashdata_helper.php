<?php
/**
 * Sets a "one-time" flash message into the session.
 * * Stores a message in the session that will persist only until it is 
 * retrieved by flashdata(). This is the first half of the "set it and 
 * forget it" pattern, typically followed by a redirect.
 * * @param string $msg The message to store (e.g., 'Record successfully nuked').
 * @return void
 * * @example set_flashdata('Record successfully nuked'); redirect('trash/empty');
 */
function set_flashdata(string $msg): void {
    $_SESSION['flashdata'] = $msg;
}

/**
 * Retrieve and display a "one-time" flash message from the session.
 *
 * Checks for a message stored in $_SESSION['flashdata']. If found, the message 
 * is returned wrapped in HTML and the session variable is immediately cleared 
 * so it never displays again.
 * * Customization Hierarchy:
 * 1. Function arguments (one-off override).
 * 2. FLASHDATA_OPEN/CLOSE constants from config/config.php (global default).
 * 3. Default green <p> tag (fallback).
 *
 * @param string|null $opening_html Optional HTML opening tag to wrap the message.
 * @param string|null $closing_html Optional HTML closing tag to wrap the message.
 * @return string|null The formatted message string, or null if no flashdata exists.
 */
function flashdata(?string $opening_html = null, ?string $closing_html = null): ?string {

    if (!isset($_SESSION['flashdata'])) {
        return null;
    }

    $flashdata = $_SESSION['flashdata'];
    unset($_SESSION['flashdata']);

    if (!isset($opening_html)) {
        if (defined('FLASHDATA_OPEN') && defined('FLASHDATA_CLOSE')) {
            $opening_html = FLASHDATA_OPEN;
            $closing_html = FLASHDATA_CLOSE;
        } else {
            $opening_html = '<p style="color: green;">';
            $closing_html = '</p>';
        }
    }

    return $opening_html . $flashdata . $closing_html;
}