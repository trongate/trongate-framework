<?php
/**
 * SMTP Email Configuration for Trongate Email Module
 *
 * Copy this file to your application's config directory and
 * update the values below with your SMTP server credentials.
 *
 * Expected location: APPPATH . '/config/trongate_email.php'
 */
$config['trongate_email'] = [

    // Your SMTP server hostname
    'smtp_host' => '',

    // SMTP port (465 for SSL, 587 for STARTTLS)
    'smtp_port' => 465,

    // SMTP username (also used as the 'From' email address)
    'smtp_user' => '',

    // SMTP password
    'smtp_pass' => '',

    // Security: 'ssl', 'tls', or '' for no encryption
    'smtp_secure' => 'ssl',

    // Optional: Display name shown as the sender
    'smtp_from_name' => 'Trongate Support'
];
