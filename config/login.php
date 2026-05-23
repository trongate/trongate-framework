<?php
/**
 * Login Module Configuration
 *
 * Defines authentication rules per user level. Each user level maps to a
 * target database table and specifies which columns serve as login identifiers,
 * where to redirect after login, and which view file to use for the login form.
 *
 * Expected location: APPPATH . '/config/login.php'
 */
$config['login'] = [

    // -----------------------------------------------------------------
    // Global settings (applied to all user levels)
    // -----------------------------------------------------------------

    // Fallback view file if a user level doesn't specify its own
    'default_view_file' => 'login_default',

    // When at least one user level defines a secret_login_word, numeric
    // level IDs in the URL are no longer accepted — the correct secret
    // word must be present to reach that level's login form.

    // Number of failed login attempts before the account is temporarily blocked
    'max_failed_attempts' => 3,

    // How long an account stays blocked after exceeding max_failed_attempts (seconds)
    'block_duration' => 900,

    // Bcrypt cost factor for password hashing (higher = more secure but slower)
    'password_hash_cost' => 11,

    // Lifespan of a password-reset token (seconds)
    'reset_token_lifespan' => 3600,

    // -----------------------------------------------------------------
    // User-level definitions
    // -----------------------------------------------------------------
    //
    // The array key matches a row ID in the `trongate_user_levels` table.
    //
    // Each level defines:
    //
    //   target_table        — The database table that stores this level's user records.
    //
    //   user_ref_field      — The column in target_table that holds a foreign key
    //                         referencing `trongate_users.id`.
    //
    //   redirect_on_success — Where the user is sent after a successful login
    //                         (format: "module/method").
    //
    //   allow_remember      — Whether "Remember Me" is offered on the login form
    //                         (0 = no, 1 = yes).
    //
    //   remember_days       — How long the "Remember Me" cookie lasts (in days).
    //                         Only relevant when allow_remember is 1.
    //
    //   view_file           — The view file used to render the login form for this level.
    //                         Looked up in modules/login/views/.
    //
    //   fields.identifiers  — Columns that can be used to identify the user during login.
    //                         Users can log in with any one of these. Typical choices
    //                         are username, email, or both.
    //
    //   fields.password     — The column that stores the hashed password.
    //
    //   Each identifier and the password entry accepts:
    //     column — The actual column name in target_table.
    //     label  — The human-readable label shown on the login form.

    'user_levels' => [

        // ── Administrator accounts ──────────────────────────────────
        1 => [
            'target_table'        => 'trongate_administrators',
            'user_ref_field'      => 'trongate_user_id',
            'secret_login_word'       => 'tg-admin',
            'redirect_on_success'     => 'trongate_administrators/manage',
            'allow_remember'          => 0,
            'remember_days'           => 0,
            'enable_forgot_password'  => false,
            'view_file'               => 'login_default',
            'fields'              => [
                'identifiers' => [
                    'username' => [
                        'column' => 'username',
                        'label'  => 'Username'
                    ],
                    'email'    => [
                        'column' => 'email',
                        'label'  => 'Email'
                    ]
                ],
                'password' => [
                    'column' => 'password',
                    'label'  => 'Password'
                ]
            ]
        ],

        // ── Additional user levels ──────────────────────────────────
        //
        // To add another user level, create a matching row in
        // `trongate_user_levels`, then add a new entry here keyed
        // by that row's ID.  A commented-out example is shown below.
        //
        // To use this example:
        //   1. Insert a row into trongate_user_levels with ID = 2
        //      (or use whichever ID fits your schema)
        //   2. Uncomment the block below
        //   3. Create your members table (or point target_table at
        //      an existing one)
        //
        // 2 => [
        //     'target_table'        => 'members',
        //     'user_ref_field'      => 'trongate_user_id',
        //     'secret_login_word'       => 'members-area',
        //     'redirect_on_success'     => 'members/dashboard',
        //     'allow_remember'          => 1,   // Members often expect 'remember me'
        //     'remember_days'           => 30,  // Keep logged in for 30 days
        //     'enable_forgot_password'  => false,
        //     'view_file'               => 'login_default',
        //     'fields'              => [
        //         'identifiers' => [
        //             'email' => [
        //                 'column' => 'email',
        //                 'label'  => 'Email Address'
        //             ]
        //         ],
        //         'password' => [
        //             'column' => 'password',
        //             'label'  => 'Password'
        //         ]
        //     ]
        // ]

    ]
];
