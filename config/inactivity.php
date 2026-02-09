<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Session Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | These values control the inactivity auto-logout feature for each role.
    | Values are in minutes.
    |
    */

    'timeout' => [
        'admin' => (int) env('SESSION_TIMEOUT_ADMIN', 15),
        'teacher' => (int) env('SESSION_TIMEOUT_TEACHER', 30),
        'student' => (int) env('SESSION_TIMEOUT_STUDENT', 60),
        'warning' => (int) env('SESSION_WARNING_MINUTES', 2),
    ],
];
