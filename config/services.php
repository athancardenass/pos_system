<?php

return [
    'allow_loopback_http' => env('INTEGRATION_ALLOW_LOOPBACK_HTTP', false),
    'pos_instance_id' => env('POS_INSTANCE_ID'),
    'inventory_webhook_token' => env('INTEGRATION_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'crm' => [
        'base_url' => env('CRM_API_BASE_URL'),
        'token' => env('CRM_API_TOKEN'),
    ],

    'hrms' => [
        'base_url' => env('HRMS_API_BASE_URL'),
        'token' => env('HRMS_API_TOKEN'),
        'allow_http' => env('HRMS_ALLOW_HTTP', false),
        'employees_path' => env('HRMS_EMPLOYEES_PATH', '/api/v1/employees'),
    ],

    'inventory' => [
        'base_url' => env('INVENTORY_API_BASE_URL'),
        'token' => env('INVENTORY_API_TOKEN'),
        'allow_http' => env('INVENTORY_ALLOW_HTTP', false),
    ],

];
