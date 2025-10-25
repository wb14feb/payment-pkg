<?php

// config for AnyTech/Jinah
return [
    "default_service" => env('JINAH_DEFAULT_SERVICE', 'finpay'),
    "environment" => env('JINAH_ENVIRONMENT', 'development'),
    
    "services" => [
        "finpay" => [
            "driver" => "finpay",
            "name" => "FinPay",
            "description" => "FinPay Payment Gateway",
            "development_url" => env('JINAH_FINPAY_SANDBOX_URL', 'https://devo.finnet.co.id'),
            "production_url" => env('JINAH_FINPAY_PRODUCTION_URL', 'https://live.finnet.co.id'),
            "client_id" => env('JINAH_FINPAY_CLIENT_ID', env('FINPAY_CLIENT_ID')),
            "client_secret" => env('JINAH_FINPAY_CLIENT_SECRET', env('FINPAY_CLIENT_SECRET')),
            "connect_timeout" => env('JINAH_FINPAY_CONNECT_TIMEOUT', 10),
            "verify_ssl" => env('JINAH_FINPAY_VERIFY_SSL', true),
        ],
    ],
    
    // Webhook configuration
    "webhook" => [
        "route_prefix" => env('JINAH_WEBHOOK_PREFIX', 'payment-webhook'),
        "global" => [
            "ip_whitelist" => env('JINAH_WEBHOOK_IP_WHITELIST'), // Comma-separated IPs or CIDR blocks
        ],
        "finpay" => [
            "secret" => env('JINAH_FINPAY_CLIENT_SECRET', env('FINPAY_CLIENT_SECRET')),
            "verify_signature" => env('JINAH_FINPAY_VERIFY_SIGNATURE', true),
            "ip_whitelist" => env('JINAH_FINPAY_WEBHOOK_IPS'),
        ],
        "stripe" => [
            "endpoint_secret" => env('JINAH_STRIPE_WEBHOOK_SECRET'),
            "verify_signature" => env('JINAH_STRIPE_VERIFY_SIGNATURE', true),
            "ip_whitelist" => env('JINAH_STRIPE_WEBHOOK_IPS'),
        ],
        "midtrans" => [
            "server_key" => env('JINAH_MIDTRANS_SERVER_KEY'),
            "verify_signature" => env('JINAH_MIDTRANS_VERIFY_SIGNATURE', true),
            "ip_whitelist" => env('JINAH_MIDTRANS_WEBHOOK_IPS'),
        ],
    ],
    
    // Payment configuration
    "payment" => [
        "default_currency" => env('JINAH_DEFAULT_CURRENCY', 'IDR'),
        "callback_url" => env('JINAH_CALLBACK_URL'),
        "return_url" => env('JINAH_RETURN_URL'),
    ],
    
    // Logging
    "logging" => [
        "enabled" => env('JINAH_LOGGING_ENABLED', false),
        "level" => env('JINAH_LOGGING_LEVEL', 'info'),
        "channel" => env('JINAH_LOGGING_CHANNEL', 'single'),
    ],
];
