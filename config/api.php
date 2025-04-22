<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify configuration settings for your API
    |
    */

    'base_url' => env('API_BASE_URL', 'http://127.0.0.1:8000/api'),
    
    'version' => env('API_VERSION', 'v1'),

    'throttle' => [
        'max_attempts' => env('API_THROTTLE_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('API_THROTTLE_DECAY_MINUTES', 1),
    ],

    'pagination' => [
        'per_page' => env('API_PAGINATION_PER_PAGE', 10),
    ],
]; 