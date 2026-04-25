<?php
/**
 * Mensajes de error de validación por defecto en español para Trongate v2
 * Incluye validación estándar y las nuevas funciones de archivos e imágenes.
 */
$validation_errors = [

    // Reglas Estándar
    'required_error'             => 'El campo [label] es obligatorio.',
    'integer_error'              => 'El campo [label] debe contener un número entero.',
    'numeric_error'              => 'El campo [label] debe contener un valor numérico.',
    'decimal_error'              => 'El campo [label] debe contener un número decimal.',
    'valid_email_error'          => 'El campo [label] debe contener una dirección de correo electrónico válida.',
    'valid_url_error'            => 'El campo [label] debe contener una URL válida.',
    'valid_ip_error'             => 'El campo [label] debe contener una dirección IP válida.',
    'valid_date_error'           => 'El campo [label] debe ser una fecha válida en formato AAAA-MM-DD.',
    'valid_time_error'           => 'El campo [label] debe ser una hora válida en formato HH:MM o HH:MM:SS.',
    'valid_datetime_local_error' => 'El campo [label] debe ser una fecha y hora válida en formato AAAA-MM-DDTHH:MM.',
    'valid_month_error'          => 'El campo [label] debe ser un mes válido en formato AAAA-MM.',
    'valid_week_error'           => 'El campo [label] debe ser una semana válida en formato AAAA-W##.',
    'min_length_error'           => 'El campo [label] debe tener al menos [param] caracteres.',
    'max_length_error'           => 'El campo [label] no puede exceder los [param] caracteres.',
    'exact_length_error'         => 'El campo [label] debe tener exactamente [param] caracteres.',
    'greater_than_error'         => 'El campo [label] debe ser mayor que [param].',
    'less_than_error'            => 'El campo [label] debe ser menor que [param].',
    'matches_error'              => 'El campo [label] debe coincidir con el campo [param].',

    // Reglas de Validación de Archivos
    'allowed_types_error'        => 'El archivo [label] debe ser uno de los siguientes tipos: [param].',
    'max_size_error'             => 'El tamaño del archivo [label] no puede exceder los [param] KB.',
    'min_size_error'             => 'El tamaño del archivo [label] debe ser de al menos [param] KB.',
    
    // Reglas de Validación de Imágenes
    'is_image_error'             => 'El archivo [label] debe ser una imagen válida.',
    'max_width_error'            => 'El ancho de la imagen [label] no puede exceder los [param] píxeles.',
    'min_width_error'            => 'El ancho de la imagen [label] debe ser de al menos [param] píxeles.',
    'max_height_error'           => 'El alto de la imagen [label] no puede exceder los [param] píxeles.',
    'min_height_error'           => 'El alto de la imagen [label] debe ser de al menos [param] píxeles.',
    'exact_width_error'          => 'El ancho de la imagen [label] debe ser exactamente de [param] píxeles.',
    'exact_height_error'         => 'El alto de la imagen [label] debe ser exactamente de [param] píxeles.',
    'square_error'               => 'La imagen [label] debe ser cuadrada (ancho y alto iguales).',
    
    // Errores Generales de Carga y Seguridad
    'upload_failed_error'        => 'La carga de [label] ha fallado. Por favor, inténtelo de nuevo.',
    'not_an_image_error'         => 'El archivo [label] debe ser una imagen válida (JPG, PNG, GIF o WEBP).',
    'invalid_file_error'         => 'El archivo [label] no es válido o está dañado.',
    'security_threat_error'      => 'Se ha detectado una amenaza de seguridad en el archivo [label]. La carga ha sido bloqueada.',

    // Custom Rules
    'title_check' => 'No puedes estar bromeando!'
];