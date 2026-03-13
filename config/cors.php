<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie','logout'],

    'allowed_methods' => ['*'],
// OU plus sécurisé :
'allowed_origins' => ['https://blood-5frzrbgbj-djeukouhugues0505-8791s-projects.vercel.app',
    'http://localhost:8080'], // Garde le local pour tes tests],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [''],

    'max_age' => 0,

    'supports_credentials' => true,

];
