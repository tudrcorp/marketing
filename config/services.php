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

    'marketing_api' => [
        'base_url' => env('MARKETING_API_URL', 'http://localhost:4000'),
        'key' => env('MARKETING_API_KEY'),
        'timeout' => (int) env('MARKETING_API_TIMEOUT', 5),
        'batch_timeout' => (int) env('MARKETING_API_BATCH_TIMEOUT', 30),
        'email_timeout' => (int) env('MARKETING_API_EMAIL_TIMEOUT', 300),
        'birthday_test_send_path' => env('MARKETING_API_BIRTHDAY_TEST_SEND_PATH', '/api/notifications/birthday/test'),
        'bulk_emails_path' => env('MARKETING_API_BULK_EMAILS_PATH', '/api/emails/bulk'),
        'mass_send_path' => env('MARKETING_API_MASS_SEND_PATH', '/api/notifications/mass/send'),
        'mass_send_batch_path' => env('MARKETING_API_MASS_SEND_BATCH_PATH', '/api/notifications/mass/send-batch'),
        'birthday_email_batch_size' => (int) env('MARKETING_BIRTHDAY_EMAIL_BATCH_SIZE', 50),
        'mass_email_batch_size' => (int) env('MARKETING_MASS_EMAIL_BATCH_SIZE', 50),
        'mass_email_batch_pause_seconds' => (int) env('MARKETING_MASS_EMAIL_BATCH_PAUSE_SECONDS', 5),
        'mass_email_quota_cooldown_minutes' => (int) env('MARKETING_MASS_EMAIL_QUOTA_COOLDOWN_MINUTES', 60),
        'mass_email_auth_cooldown_minutes' => (int) env('MARKETING_MASS_EMAIL_AUTH_COOLDOWN_MINUTES', 15),
        'whatsapp_batch_size' => (int) env('MARKETING_WHATSAPP_BATCH_SIZE', 50),
        'whatsapp_batch_pause_seconds' => (int) env('MARKETING_WHATSAPP_BATCH_PAUSE_SECONDS', 15),
    ],

];
