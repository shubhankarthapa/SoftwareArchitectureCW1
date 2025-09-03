<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'default_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Microservices Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the various Laravel microservices that this
    | application communicates with.
    |
    */

    'user_service' => [
        'url' => env('USER_SERVICE_URL', 'http://localhost:8001'),
    ],

    'hotel_service' => [
        'url' => env('HOTEL_SERVICE_URL', 'http://localhost:8002'),
    ],

    'booking_service' => [
        'url' => env('BOOKING_SERVICE_URL', 'http://localhost:8003'),
    ],

    'wallet_service' => [
        'url' => env('WALLET_SERVICE_URL', 'http://localhost:8004'),
    ],

    'logs_service' => [
        'url' => env('LOGS_SERVICE_URL', 'http://127.0.0.1:8001/api/logs'),
    ],

];
