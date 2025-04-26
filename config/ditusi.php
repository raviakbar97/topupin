<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ditusi API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the Ditusi API.
    | Development credentials are used by default.
    |
    */

    'base_url' => env('DITUSI_BASE_URL', 'https://api.ditusi.co.id/api/v1'),
    
    'dev_base_url' => env('DITUSI_DEV_BASE_URL', 'https://api.ditusi.co.id/api/dev/v1'),
    
    'client_id' => env('DITUSI_CLIENT_ID', '421341515'),
    
    'client_key' => env('DITUSI_CLIENT_KEY', '0spOZMo16aFQLKwKz'),
    
    'token_cache_time' => env('DITUSI_TOKEN_CACHE_TIME', 600), // in seconds (10 minutes)
]; 