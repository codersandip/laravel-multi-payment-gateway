<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    */
    'default' => env('PAYMENT_GATEWAY', 'razorpay'),

    /*
    |--------------------------------------------------------------------------
    | Failover Gateways
    |--------------------------------------------------------------------------
    */
    'failovers' => [
        // 'stripe', 'cashfree'
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('PAYMENT_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    */
    'retries' => [
        'attempts' => env('PAYMENT_RETRY_ATTEMPTS', 2),
        'sleep'    => env('PAYMENT_RETRY_SLEEP_MS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard Response Format
    |--------------------------------------------------------------------------
    */
    'format_responses' => true,

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways configuration
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'razorpay' => [
            'key_id'     => env('RAZORPAY_KEY'),
            'key_secret' => env('RAZORPAY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'test_mode'  => env('RAZORPAY_TEST_MODE', true),
        ],

        'payu' => [
            'merchant_key' => env('PAYU_MERCHANT_KEY'),
            'salt'         => env('PAYU_SALT'),
            'test_mode'    => env('PAYU_TEST_MODE', true),
        ],

        'stripe' => [
            'public_key' => env('STRIPE_KEY'),
            'secret_key' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'test_mode'  => env('STRIPE_TEST_MODE', true),
        ],

        'cashfree' => [
            'app_id'     => env('CASHFREE_APP_ID'),
            'secret_key' => env('CASHFREE_SECRET_KEY'),
            'test_mode'  => env('CASHFREE_TEST_MODE', true),
        ],
    ],
];
