<?php

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'seed' => [
        'admin_email' => env('SEED_ADMIN_EMAIL', 'superadmin@hms.local'),
        'admin_password' => env('SEED_ADMIN_PASSWORD'),
    ],

    'audit' => [
        'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 2555),
        'immutable' => true,
    ],

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

];
