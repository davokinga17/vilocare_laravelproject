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

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
    ],

    'africastalking' => [
        'base_url' => env('AFRICASTALKING_BASE_URL', 'https://api.africastalking.com'),
        'sandbox_base_url' => env('AFRICASTALKING_SANDBOX_BASE_URL', 'https://api.sandbox.africastalking.com'),
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM'),
        'sandbox' => (bool) env('AFRICASTALKING_SANDBOX', false),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
        'endpoint' => env('OPENROUTER_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'),
        'site_url' => env('OPENROUTER_SITE_URL', env('APP_URL')),
        'app_name' => env('OPENROUTER_APP_NAME', env('APP_NAME', 'ViLoCare')),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'enabled' => env('RECAPTCHA_ENABLED', true),
    ],

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'api_user' => env('MTN_MOMO_API_USER'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'target_environment' => env('MTN_MOMO_TARGET_ENVIRONMENT', 'sandbox'),
        'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
        'timeout' => env('MTN_MOMO_TIMEOUT', 30),
    ],

    'pesapal' => [
        'base_url' => env('PESAPAL_BASE_URL', 'https://cybqa.pesapal.com/pesapalv3/api'),
        'consumer_key' => env('PESAPAL_CONSUMER_KEY'),
        'consumer_secret' => env('PESAPAL_CONSUMER_SECRET'),
        'ipn_notification_id' => env('PESAPAL_IPN_NOTIFICATION_ID'),
        'timeout' => env('PESAPAL_TIMEOUT', 30),
    ],

    'mastercard' => [
        'base_url' => env('MASTERCARD_BASE_URL', 'https://ap-gateway.mastercard.com'),
        'merchant_id' => env('MASTERCARD_MERCHANT_ID'),
        'api_password' => env('MASTERCARD_API_PASSWORD'),
        'api_version' => env('MASTERCARD_API_VERSION', '100'),
        'merchant_name' => env('MASTERCARD_MERCHANT_NAME', env('APP_NAME', 'ViLoCare')),
        'merchant_url' => env('MASTERCARD_MERCHANT_URL', env('APP_URL')),
        'timeout' => env('MASTERCARD_TIMEOUT', 30),
    ],

];
