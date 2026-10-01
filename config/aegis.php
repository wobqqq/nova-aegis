<?php

declare(strict_types=1);

return [

    /*
    | The cache store holding the settings and the last dependency audit.
    | null uses the application's default store.
    */
    'cache_store' => env('AEGIS_CACHE_STORE'),

    /*
    | The administrators to check for accounts nobody signs in with any more.
    | Leave last_login_column null when the user model does not record it.
    */
    'users' => [
        'model' => env('AEGIS_USER_MODEL', 'App\\Models\\User'),
        'last_login_column' => env('AEGIS_USER_LAST_LOGIN_COLUMN'),
        'stale_after_days' => (int)env('AEGIS_USER_STALE_AFTER_DAYS', 90),
    ],

    /*
    | composer audit runs from the application's root; schedule it daily so the
    | dashboard always shows a recent result.
    */
    'audit' => [
        'schedule' => (bool)env('AEGIS_AUDIT_SCHEDULE', true),
        'timeout' => 120,
        'binary' => env('AEGIS_COMPOSER_BINARY', 'composer'),
    ],

    'scanners' => [
        'http_timeout' => 10,
        'http_concurrency' => 7,
        'tcp_timeout' => 2,
        'tls_timeout' => 10,
    ],
];
