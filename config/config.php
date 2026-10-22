<?php
/**
 * AgriRent configuration.
 * Adjust the database credentials if your XAMPP MySQL user/password differ.
 */
return [
    'app' => [
        'name'     => 'AgriRent',
        'debug'    => true,              // show detailed errors (turn off in production)
        'timezone' => 'Asia/Dhaka',
        'url'      => '',                // leave empty to detect automatically, e.g. http://localhost/agri_rent_system
    ],

    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => getenv('AGRIRENT_DB_NAME') ?: 'agriproject',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    /*
     * Mail drivers:
     *   log  - emails are saved to storage/mail and can be read at index.php?r=mailbox (default, works offline)
     *   smtp - real delivery, e.g. Gmail: host smtp.gmail.com, port 587, encryption tls, an App Password
     *   mail - PHP mail() using php.ini sendmail settings
     */
    'mail' => [
        'driver'     => 'log',
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',           // tls | ssl | none
        'username'   => '',
        'password'   => '',
        'from_email' => 'no-reply@agrirent.local',
        'from_name'  => 'AgriRent',
    ],

    /*
     * "Continue with Google" (OAuth 2.0). Create credentials at https://console.cloud.google.com/apis/credentials
     * Authorized redirect URI: <your app url>/index.php?r=auth/google-callback
     */
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],

    'security' => [
        'email_link_hours'      => 24,   // email verification link lifetime
        'reset_link_minutes'    => 30,   // password reset link lifetime
        'otp_seconds'           => 120,  // OTP (reset + 2FA) lifetime
        'otp_max_attempts'      => 5,
        'remember_days'         => 30,
    ],

    'uploads' => [
        'max_image_mb'    => 5,
        'max_document_mb' => 5,
    ],
];
