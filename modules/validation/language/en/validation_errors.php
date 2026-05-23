<?php
/**
 * Default English validation error messages for Trongate v2
 * Includes standard form validation and new file/image validation rules.
 */
$validation_errors = [

    // Standard Rules
    'required_error'             => 'The [label] field is required.',
    'integer_error'              => 'The [label] field must be an integer.',
    'numeric_error'              => 'The [label] field must be numeric.',
    'decimal_error'              => 'The [label] field must be a decimal number.',
    'valid_email_error'          => 'The [label] field must contain a valid email address.',
    'valid_url_error'            => 'The [label] field must contain a valid URL.',
    'valid_ip_error'             => 'The [label] field must contain a valid IP address.',
    'valid_date_error'           => 'The [label] field must be a valid date in YYYY-MM-DD format.',
    'valid_time_error'           => 'The [label] field must be a valid time in HH:MM or HH:MM:SS format.',
    'valid_datetime_local_error' => 'The [label] field must be a valid datetime in YYYY-MM-DDTHH:MM format.',
    'valid_month_error'          => 'The [label] field must be a valid month in YYYY-MM format.',
    'valid_week_error'           => 'The [label] field must be a valid week in YYYY-W## format.',
    'min_length_error'           => 'The [label] field must be at least [param] characters long.',
    'max_length_error'           => 'The [label] field cannot exceed [param] characters.',
    'exact_length_error'         => 'The [label] field must be exactly [param] characters long.',
    'greater_than_error'         => 'The [label] field must be greater than [param].',
    'less_than_error'            => 'The [label] field must be less than [param].',
    'matches_error'              => 'The [label] field must match the [param] field.',

    // File Validation Rules
    'allowed_types_error'        => 'The [label] must be one of the following file types: [param].',
    'max_size_error'             => 'The [label] file size must not exceed [param] KB.',
    'min_size_error'             => 'The [label] file size must be at least [param] KB.',
    
    // Image Validation Rules
    'is_image_error'             => 'The [label] must be a valid image file.',
    'max_width_error'            => 'The [label] image width cannot exceed [param] pixels.',
    'min_width_error'            => 'The [label] image width must be at least [param] pixels.',
    'max_height_error'           => 'The [label] image height cannot exceed [param] pixels.',
    'min_height_error'           => 'The [label] image height must be at least [param] pixels.',
    'exact_width_error'          => 'The [label] image width must be exactly [param] pixels.',
    'exact_height_error'         => 'The [label] image height must be exactly [param] pixels.',
    'square_error'               => 'The [label] image must be square (equal width and height).',
    
    // General Upload / Security Errors
    'upload_failed_error'        => 'The [label] failed to upload. Please try again.',
    'not_an_image_error'         => 'The [label] must be a valid image file (JPG, PNG, GIF, or WEBP).',
    'invalid_file_error'         => 'The [label] is invalid or has been corrupted.',
    'security_threat_error'      => 'A security threat was detected in the [label]. The upload has been blocked.',

    // Custom Rules
    'title_check' => 'You cannot be serious'
];