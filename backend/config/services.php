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

    // Provider IA utilisé par les services (openai|gemini)
    'ai_provider' => env('AI_PROVIDER', 'openai'),

    'gemini' => [
        // API_KEY reste compatible, mais privilégier GEMINI_API_KEY
        'api_key' => env('GEMINI_API_KEY', env('API_KEY')),
        // Utiliser l'API Gemini (v1beta par défaut pour generateContent)
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash-latest'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'api_url' => env('OPENAI_API_URL'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],


];
