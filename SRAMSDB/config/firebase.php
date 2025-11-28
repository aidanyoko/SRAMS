<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase for Real-Time Seat Reservations
    |--------------------------------------------------------------------------
    |
    | This configuration is ONLY for seat reservation sync.
    | User authentication still handled by Laravel/MySQL.
    |
    */

    'enabled' => env('FIREBASE_ENABLED', false),

    'config' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
    ],

    // Your school/institution identifier for Firestore paths
    'institution_id' => env('FIREBASE_INSTITUTION_ID', 'default-institution'),
];