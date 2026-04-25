<?php
/**
 * Flashdata Module - Framework Service for One-Time Session Messages
 * 
 * This module provides flash message functionality as a framework service.
 * It is accessed exclusively via Service Routing from helper functions.
 * 
 * Security: Uses BASE_URL check instead of block_url() to minimize dependencies
 * and maximize performance for core framework services.
 */

// Prevent direct script access - lightweight security check
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class Flashdata extends Trongate {

    /**
     * Sets a "one-time" flash message into the session.
     * 
     * Stores a message in the session that will persist only until it is 
     * retrieved by the flashdata() method. This is the first half of the 
     * "set it and forget it" pattern, typically followed by a redirect.
     *
     * @param string $msg The message to store (e.g., 'Record successfully nuked').
     * @return void
     */
    public function set_flashdata(string $msg): void {
        $_SESSION['flashdata'] = $msg;
    }

    /**
     * Retrieve and display a "one-time" flash message from the session.
     *
     * Checks for a message stored in $_SESSION['flashdata']. If found, 
     * the message is returned wrapped in HTML and the session variable 
     * is immediately cleared so it never displays again.
     *
     * Customization Hierarchy:
     * 1. Function arguments (one-off override).
     * 2. FLASHDATA_OPEN/CLOSE constants from config/config.php (global default).
     * 3. Default green <p> tag (fallback).
     *
     * @param array $data Array containing 'opening_html' and 'closing_html' keys.
     * @return string|null The formatted message string, or null if no flashdata exists.
     */
    public function flashdata(array $data = []): ?string {
        if (!isset($_SESSION['flashdata'])) {
            return null;
        }

        $flashdata = $_SESSION['flashdata'];
        unset($_SESSION['flashdata']);

        // Extract bundled data from the helper
        $opening_html = $data['opening_html'] ?? null;
        $closing_html = $data['closing_html'] ?? null;

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
}