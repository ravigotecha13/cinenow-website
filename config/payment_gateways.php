<?php

return [
    /*
     * Central registry for payment gateways.
     * Used by checkout dropdowns and app-configuration API.
     */
    'gateways' => [
        'stripe' => [
            'enabled_setting' => 'str_payment_method',
            'nested_key' => 'stripe_pay',
            'settings' => ['stripe_secretkey', 'stripe_publickey'],
        ],
        'razorpay' => [
            'enabled_setting' => 'razor_payment_method',
            'nested_key' => 'razor_pay',
            'settings' => ['razorpay_secretkey', 'razorpay_publickey'],
        ],
        'paystack' => [
            'enabled_setting' => 'paystack_payment_method',
            'nested_key' => 'paystack_pay',
            'settings' => ['paystack_secretkey', 'paystack_publickey'],
        ],
        'paypal' => [
            'enabled_setting' => 'paypal_payment_method',
            'nested_key' => 'paypal_pay',
            'settings' => ['paypal_secretkey', 'paypal_clientid'],
        ],
        'flutterwave' => [
            'enabled_setting' => 'flutterwave_payment_method',
            'nested_key' => 'flutterwave_pay',
            'settings' => ['flutterwave_secretkey', 'flutterwave_publickey'],
        ],
        'cinet' => [
            'enabled_setting' => 'cinet_payment_method',
            'nested_key' => 'cinet_pay',
            'settings' => ['cinet_siteid', 'cinet_api_key', 'cinet_Secret_key'],
        ],
        'sadad' => [
            'enabled_setting' => 'sadad_payment_method',
            'nested_key' => 'sadad_pay',
            'settings' => ['sadad_Sadadkey', 'sadad_id_key', 'sadad_Domain'],
        ],
        'airtel' => [
            'enabled_setting' => 'airtel_payment_method',
            'nested_key' => 'airtel_pay',
            'settings' => ['airtel_money_secretkey', 'airtel_money_client_id'],
        ],
        'phonepe' => [
            'enabled_setting' => 'phonepe_payment_method',
            'nested_key' => 'phonepe_pay',
            'settings' => ['phonepe_App_id', 'phonepe_Merchant_id', 'phonepe_salt_key', 'phonepe_salt_index'],
        ],
        'midtrans' => [
            'enabled_setting' => 'midtrans_payment_method',
            'nested_key' => 'midtrans_pay',
            'settings' => ['midtrans_client_id', 'midtrans_server_key'],
        ],
        'hyperpay' => [
            'enabled_setting' => 'hyperpay_payment_method',
            'nested_key' => 'hyperpay_pay',
            'settings' => [
                'hyperpay_entity_id',
                'hyperpay_access_token',
                'hyperpay_base_url',
                'hyperpay_currency',
                'hyperpay_payment_type',
                'hyperpay_brands',
            ],
        ],
    ],
];

