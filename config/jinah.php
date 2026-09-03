<?php

// config for AnyTech/Jinah
return [
    "default_service" => env('JINAH_DEFAULT_SERVICE', 'jinah'),
    "environment" => env('JINAH_ENVIRONMENT', 'development'),
    
    "services" => [
        "jinah" => [
            "driver" => "jinah",
            "name" => "Jinah Default",
            "description" => "Default Jinah Payment Service",
            "channels" => [
                "qris" => [
                    "category" => "qr",
                    "name" => "QRIS",
                    "enabled" => env('JINAH_CHANNEL_QRIS_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_QRIS_FEE', env('JINAH_CHANNEL_QR_FEE', 0)),
                    "service" => env('JINAH_CHANNEL_QRIS_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_QRIS_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg"),
                ],
                "cc" => [
                    "category" => "cc",
                    "name" => "Credit Card",
                    "enabled" => env('JINAH_CHANNEL_CC_ENABLED', true),
                    "fee" => "2.5% + 2000",
                    "service" => env('JINAH_CHANNEL_CC_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_CC_ICON_URL', "https://c.ekstatic.net/ecl/logos/payment-options/master-visa-logo.svg"),
                ],
                "vabca" => [
                    "category" => "va",
                    "name" => "Virtual Account BCA",
                    "enabled" => env('JINAH_CHANNEL_VABCA_ENABLED', false),
                    "fee" => env('JINAH_CHANNEL_VABCA_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VABCA_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VABCA_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg"),
                ],
                "vabri" => [
                    "category" => "va",
                    "name" => "Virtual Account BRI",
                    "enabled" => env('JINAH_CHANNEL_VABRI_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VABRI_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VABRI_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VABRI_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/2/2e/BRI_2020.svg"),
                ],
                "vamandiri" => [
                    "category" => "va",
                    "name" => "Virtual Account Mandiri",
                    "enabled" => env('JINAH_CHANNEL_VAMANDIRI_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VAMANDIRI_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VAMANDIRI_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VAMANDIRI_ICON_URL', "https://upload.wikimedia.org/wikipedia/id/f/fa/Bank_Mandiri_logo.svg"),
                ],
                "vabni" => [
                    "category" => "va",
                    "name" => "Virtual Account BNI",
                    "enabled" => env('JINAH_CHANNEL_VABNI_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VABNI_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VABNI_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VABNI_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/f/f0/Bank_Negara_Indonesia_logo_%282004%29.svg"),
                ],
                "vabsi" => [
                    "category" => "va",
                    "name" => "Virtual Account BSI",
                    "enabled" => env('JINAH_CHANNEL_VABSI_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VABSI_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VABSI_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VABSI_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/Bank_Syariah_Indonesia.svg/960px-Bank_Syariah_Indonesia.svg.png"),
                ],
                "vadanamon" => [
                    "category" => "va",
                    "name" => "Virtual Account Danamon",
                    "enabled" => env('JINAH_CHANNEL_VADANAMON_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VADANAMON_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VADANAMON_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VADANAMON_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/7/7b/Danamon.svg"),
                ],
                "vabtn" => [
                    "category" => "va",
                    "name" => "Virtual Account BTN",
                    "enabled" => env('JINAH_CHANNEL_VABTN_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VABTN_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VABTN_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VABTN_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/c/ca/BTN_2024.svg"),
                ],
                "vacimb" => [
                    "category" => "va",
                    "name" => "Virtual Account CIMB",
                    "enabled" => env('JINAH_CHANNEL_VACIMB_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VACIMB_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VACIMB_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VACIMB_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/3/38/CIMB_Niaga_logo.svg"),
                ],
                "vapermata" => [
                    "category" => "va",
                    "name" => "Virtual Account Permata",
                    "enabled" => env('JINAH_CHANNEL_VAPERMATA_ENABLED', true),
                    "fee" => env('JINAH_CHANNEL_VAPERMATA_FEE', env('JINAH_CHANNEL_VA_FEE', 4000)),
                    "service" => env('JINAH_CHANNEL_VAPERMATA_SERVICE', env('JINAH_CHANNEL_API_SERVICE', 'converso')),
                    "icon_url" => env('JINAH_CHANNEL_VAPERMATA_ICON_URL', "https://upload.wikimedia.org/wikipedia/commons/f/ff/Permata_Bank_%282024%29.svg"),
                ],
                "other" => [
                    "category" => "other",
                    "name" => "Metode Lainnya",
                    "enabled" => env('JINAH_CHANNEL_OTHER_ENABLED', false),
                    "fee" => 0,
                    "service" => env('JINAH_CHANNEL_OTHER_SERVICE', 'sesari'),
                    "icon_url" => env('JINAH_CHANNEL_OTHER_ICON_URL', "https://cdn-icons-png.flaticon.com/512/5895/5895032.png"),
                ],
            ]
        ],
        "finpay" => [
            "driver" => "finpay",
            "name" => "FinPay",
            "description" => "FinPay Payment Gateway",
            "development_url" => env('JINAH_FINPAY_SANDBOX_URL', 'https://devo.finnet.co.id'),
            "production_url" => env('JINAH_FINPAY_PRODUCTION_URL', 'https://live.finnet.co.id'),
            "client_id" => env('JINAH_FINPAY_CLIENT_ID', env('FINPAY_CLIENT_ID', 'no-client-id')),
            "client_secret" => env('JINAH_FINPAY_CLIENT_SECRET', env('FINPAY_CLIENT_SECRET', 'no-secret')),
            "connect_timeout" => env('JINAH_FINPAY_CONNECT_TIMEOUT', 10),
            "verify_ssl" => env('JINAH_FINPAY_VERIFY_SSL', true),
        ],
        "doku" => [
            "driver" => "doku",
            "name" => "DOKU",
            "description" => "DOKU Direct API / Checkout Gateway",
            "development_url" => env('JINAH_DOKU_SANDBOX_URL', 'https://api-sandbox.doku.com'),
            "production_url" => env('JINAH_DOKU_PRODUCTION_URL', 'https://api.doku.com'),
            "client_id" => env('JINAH_DOKU_CLIENT_ID', env('DOKU_CLIENT_ID', 'no-client-id')),
            "secret_key" => env('JINAH_DOKU_SECRET_KEY', env('DOKU_SECRET_KEY', 'no-secret')),
            "connect_timeout" => env('JINAH_DOKU_CONNECT_TIMEOUT', 10),
            "verify_ssl" => env('JINAH_DOKU_VERIFY_SSL', true),
            "available_methods" => env('JINAH_DOKU_AVAILABLE_METHODS', 'qris,vabca,vabri,vamandiri,vabni,cc,alfamart,indomaret'),
        ],
        "sesari" => [
            "driver" => "sesari",
            "name" => "Sesari",
            "description" => "Sesari Payment Gateway",
            "development_url" => env('JINAH_SESARI_DEVELOPMENT_URL', 'https://api.explorind.app'),
            "production_url" => env('JINAH_SESARI_PRODUCTION_URL', 'https://api.explorind.app'),
            "service_uuid" => env('JINAH_SESARI_SERVICE_UUID') ?? env('SESARI_API_SERVICE_UUID'),
            "service_key" => env('JINAH_SESARI_SERVICE_KEY') ?? env('SESARI_API_SERVICE_KEY'),
            "service_va" => env('JINAH_SESARI_SERVICE_VA') ?? env('SESARI_API_SERVICE_VA') ?? '',
            "connect_timeout" => env('JINAH_SESARI_CONNECT_TIMEOUT', 10),
            "verify_ssl" => env('JINAH_SESARI_VERIFY_SSL', true),
        ],
        "converso" => [
            "driver" => "converso",
            "name" => "Converso",
            "description" => "Converso API",
            "development_url" => env('JINAH_CONVERSO_SANDBOX_URL', 'https://api.converso.id/api'),
            "production_url" => env('JINAH_CONVERSO_PRODUCTION_URL', 'https://api.converso.id/api'),
            "client_id" => env('JINAH_CONVERSO_CLIENT_ID', env('CONVERSO_CLIENT_ID', 'no-client-id')),
            "secret_key" => env('JINAH_CONVERSO_SECRET_KEY', env('CONVERSO_SECRET_KEY', 'no-secret')),
            "api_key" => env('JINAH_CONVERSO_API_KEY', env('CONVERSO_API_KEY', 'no-key')),
            "connect_timeout" => env('JINAH_CONVERSO_CONNECT_TIMEOUT', 10),
            "verify_ssl" => env('JINAH_CONVERSO_VERIFY_SSL', true),
            "store_id" => env('JINAH_CONVERSO_STORE_ID', env('CONVERSO_STORE_ID')),
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
        "doku" => [
            "secret" => env('JINAH_DOKU_SECRET_KEY', env('DOKU_SECRET_KEY')),
            "client_id" => env('JINAH_DOKU_CLIENT_ID', env('DOKU_CLIENT_ID')),
            "verify_signature" => env('JINAH_DOKU_VERIFY_SIGNATURE', true),
            "ip_whitelist" => env('JINAH_DOKU_WEBHOOK_IPS'),
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
        "converso" => [
            "secret" => env('JINAH_CONVERSO_SECRET_KEY', env('CONVERSO_SECRET_KEY')),
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
