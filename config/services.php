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

    'smartca' => [
        'base_url' => env('SMARTCA_BASE_URL', 'https://rmgateway.vnptit.vn/sca/sp769'),
        'signature_base_url' => env('SMARTCA_SIGNATURE_BASE_URL'),
        'sp_id' => env('SMARTCA_CLIENT_ID'),
        'sp_password' => env('SMARTCA_CLIENT_SECRET'),
        'serial_number' => env('SMARTCA_SERIAL_NUMBER'),
        'user_id_field' => env('SMARTCA_USER_ID_FIELD', 'smartca_user_id'),
        'default_user_id' => env('SMARTCA_DEFAULT_USER_ID'),
        'sign_type' => env('SMARTCA_SIGN_TYPE', 'hash'),
        'require_signed_pdf' => (bool) env('SMARTCA_REQUIRE_SIGNED_PDF', false),
        'pades_enabled' => (bool) env('SMARTCA_PADES_ENABLED', false),
        'pades_provider' => env('SMARTCA_PADES_PROVIDER', 'vnpt'),
        'pades_hash_encoding' => env('SMARTCA_PADES_HASH_ENCODING', 'hex'),
        'python_bin' => env('SMARTCA_PYTHON_BIN', 'python'),
        'timeout' => (int) env('SMARTCA_TIMEOUT', 30),
    ],

];
