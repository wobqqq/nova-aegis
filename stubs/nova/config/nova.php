<?php

declare(strict_types=1);

return [
    'name' => env('NOVA_APP_NAME', env('APP_NAME')),

    'domain' => env('NOVA_DOMAIN'),

    'path' => env('NOVA_PATH', '/nova'),

    'guard' => env('NOVA_GUARD'),

    'passwords' => env('NOVA_PASSWORDS'),

    'middleware' => [
        'web',
        Laravel\Nova\Http\Middleware\HandleInertiaRequests::class,
        'nova:serving',
    ],

    'api_middleware' => [
        'nova',
        Laravel\Nova\Http\Middleware\Authenticate::class,
        Laravel\Nova\Http\Middleware\Authorize::class,
    ],

    'asset_middleware' => [
        'nova:api',
        Illuminate\Http\Middleware\CheckResponseForModifications::class,
    ],

    'pagination' => 'simple',

    'storage_disk' => env('NOVA_STORAGE_DISK', 'public'),

    'currency' => 'USD',
];
