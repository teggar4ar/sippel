<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | School Information
    |--------------------------------------------------------------------------
    |
    | This configuration defines the school's basic information that will be
    | displayed in reports and other documents throughout the system.
    |
    */

    'school_name' => env('SCHOOL_NAME', 'SMP Islam Terpadu Al-Itqon'),
    'school_address' => env('SCHOOL_ADDRESS', 'Kp. Kandang Panjang RT. 01/06 Desa Tajurhalang Kec. Tajurhalang Kab. Bogor.'),

    /*
    |--------------------------------------------------------------------------
    | Default User Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the default user credentials that will be
    | used in local development environments. It is particularly useful
    | for quickly logging into the application without needing
    | to create a user manually.
    |
    */

    'default_user' => [
        'name' => env('DEFAULT_USER_NAME', 'Admin'),
        'email' => env('DEFAULT_USER_EMAIL', 'admin@example.com'),
        'password' => env('DEFAULT_USER_PASSWORD', 'password'),
    ],
];
