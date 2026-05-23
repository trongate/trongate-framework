<?php
/**
 * Messages d'erreur de validation par défaut en français pour Trongate v2
 * Inclut la validation standard et les nouvelles fonctionnalités de fichiers/images.
 */
$validation_errors = [

    // Règles Standard
    'required_error'             => 'Le champ [label] est obligatoire.',
    'integer_error'              => 'Le champ [label] doit contenir un nombre entier.',
    'numeric_error'              => 'Le champ [label] doit contenir une valeur numérique.',
    'decimal_error'              => 'Le champ [label] doit contenir un nombre décimal.',
    'valid_email_error'          => 'Le champ [label] doit contenir une adresse e-mail valide.',
    'valid_url_error'            => 'Le champ [label] doit contenir une URL valide.',
    'valid_ip_error'             => 'Le champ [label] doit contenir une adresse IP valide.',
    'valid_date_error'           => 'Le champ [label] doit être une date valide au format AAAA-MM-JJ.',
    'valid_time_error'           => 'Le champ [label] doit être une heure valide au format HH:MM ou HH:MM:SS.',
    'valid_datetime_local_error' => 'Le champ [label] doit être une date et heure valide au format AAAA-MM-JJTHH:MM.',
    'valid_month_error'          => 'Le champ [label] doit être un mois valide au format AAAA-MM.',
    'valid_week_error'           => 'Le champ [label] doit être une semaine valide au format AAAA-W##.',
    'min_length_error'           => 'Le champ [label] doit contenir au moins [param] caractères.',
    'max_length_error'           => 'Le champ [label] ne doit pas dépasser [param] caractères.',
    'exact_length_error'         => 'Le champ [label] doit contenir exactement [param] caractères.',
    'greater_than_error'         => 'Le champ [label] doit être supérieur à [param].',
    'less_than_error'            => 'Le champ [label] doit être inférieur à [param].',
    'matches_error'              => 'Le champ [label] doit correspondre au champ [param].',

    // Règles de Validation de Fichiers
    'allowed_types_error'        => 'Le fichier [label] doit être de l\'un des types suivants : [param].',
    'max_size_error'             => 'La taille du fichier [label] ne doit pas dépasser [param] Ko.',
    'min_size_error'             => 'La taille du fichier [label] doit être d\'au moins [param] Ko.',
    
    // Règles de Validation d'Images
    'is_image_error'             => 'Le fichier [label] doit être une image valide.',
    'max_width_error'            => 'La largeur de l\'image [label] ne peut pas dépasser [param] pixels.',
    'min_width_error'            => 'La largeur de l\'image [label] doit être d\'au moins [param] pixels.',
    'max_height_error'           => 'La hauteur de l\'image [label] ne peut pas dépasser [param] pixels.',
    'min_height_error'           => 'La hauteur de l\'image [label] doit être d\'au moins [param] pixels.',
    'exact_width_error'          => 'La largeur de l\'image [label] doit être exactement de [param] pixels.',
    'exact_height_error'         => 'La hauteur de l\'image [label] doit être exactement de [param] pixels.',
    'square_error'               => 'L\'image [label] doit être carrée (largeur et hauteur égales).',
    
    // Erreurs Générales de Téléchargement et Sécurité
    'upload_failed_error'        => 'Le téléchargement de [label] a échoué. Veuillez réessayer.',
    'not_an_image_error'         => 'Le fichier [label] doit être une image valide (JPG, PNG, GIF ou WEBP).',
    'invalid_file_error'         => 'Le fichier [label] est invalide ou corrompu.',
    'security_threat_error'      => 'Une menace de sécurité a été détectée dans le fichier [label]. Le téléchargement a été bloqué.',

    //Règles personnalisées
    'title_check'               => 'Tu ne peux pas être sérieux!'
];