### This is the contents of the published companion config file

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Companion Settings
    |--------------------------------------------------------------------------
    |
    | Global setting for eatcard companion package
    |
    */

    'logger' => [

        /*
        |--------------------------------------------------------------------------
        | Companion Logger
        |--------------------------------------------------------------------------
        |
        | Global setting to turn on or off logger module.
        |
        */

        'enabled' => env('COMPANION_LOGGER_STATUS', true),

        /*
        |--------------------------------------------------------------------------
        | Logger driver
        |--------------------------------------------------------------------------
        |
        | This value determines which of the following driver to use.
        |
        | check Weboccult\EatcardCompanion\Enums\LoggerTypes Class
        | Supported: "FILE" , "CLOUDWATCH"
        |
        */

        'driver'  => env('COMPANION_LOGGER_DRIVER', Weboccult\EatcardCompanion\Enums\LoggerTypes::FILE),

    ],

    /*
    |--------------------------------------------------------------------------
    | System Endpoints
    |--------------------------------------------------------------------------
    |
    | Different system's endpoint will be managed from here
    |
    */

    'system_endpoints' => [
        'pos' => env('COMPANION_POS_ENDPOINT', 'http://eatcard-pos.local'),
        'takeaway' => env('COMPANION_TAKEAWAY_ENDPOINT', 'http://eatcard-takeaway.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manage Push Notification
    |--------------------------------------------------------------------------
    |
    | Setting, URLs and more.
    |
    */
    'push_notification' => [
        'one_signal' => [
            'api_url' => 'https://onesignal.com/api/v1',
            'create_device_url' => '/players/<%onesignal_id%>?app_id=<%device_id%>',
            'send_notification_url' => '/notifications',
            'app_id' => env('ONE_SIGNAL_APP_ID', ''),
            'rest_api_key' => env('ONE_SIGNAL_REST_API_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manage Payment
    |--------------------------------------------------------------------------
    |
    | Setting, webhook, URLs and more.
    |
    */

    'payment' => [
        'gateway' => [
            'ccv' => [
                'staging'    => 'http://vpos-test.jforce.be/vpos/api/v1',
                'production' => 'https://redirect.jforce.be/api/v1',
                'endpoints'  => [
                    'createOrder' => '/payment',
                ],
                'webhook' => [
                    'pos' => [
                        'order'     => '/pos/webhook/<%id%>/<%store_id%>',
                        'sub_order' => '/pos/webhook-sub/<%id%>/<%store_id%>',
                    ],
                    'kiosk' => [
                        // will be documented in near future...
                    ],
                ],
                'returnUrl' => [
                    'pos' => '/pos',
                    'kiosk' => null, // will be documented in near future...
                ],
            ],
            'wipay' => [
                'staging'    => 'https://wipayacc.worldline.nl',
                'production' => 'https://wipay.worldline.nl',
                'endpoints'  => [
                    'createOrder' => '/api/2.0/json/debit',
                ],
                'webhook' => null, // No need, it will point to main domain directly.
            ],
            'multisafe' => [
                'mode'    => env('COMPANION_MULTISAFE_MODE', 'live'),
                'staging'    => 'https://testapi.multisafepay.com/v1/json',
                'production' => 'https://api.multisafepay.com/v1/json',
                'endpoints'  => [
                    'paymentMethod' => '/gateways',
                    'issuer' => '/issuers/IDEAL',
                    'createOrder' => '/orders',
                    'getOrder' => '/orders/<%order_id%>',
                    'refundOrder' => '/orders/<%order_id%>/refunds',
                ],
                'webhook' => [
                    'takeaway' => 'multisafe/takeaway/webhook/<%id%>/<%store_id%>',
                ],
                'redirectUrl' => [
                    'takeaway' => 'multisafe/takeaway/orders-success/<%id%>/<%store_id%>',
                ],
                'cancelUrl' => [
                    'takeaway' => 'multisafe/takeaway/cancel/<%id%>/<%store_id%>',
                ],
            ],
            'mollie' => [
                'webhook' => [
                    'takeaway' => '/webhook/<%id%>/<%store_id%>',
                ],
                'redirectUrl' => [
                    'takeaway' => '/orders-success/<%id%>/<%store_id%>',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Endpoints
    |--------------------------------------------------------------------------
    |
    | Different system's endpoint will be managed from here
    |
    */

    'aws_url' => env('COMPANION_AWS_URL', 'https://eatcard.s3.REGION-X.amazonaws.com'),
];
```
