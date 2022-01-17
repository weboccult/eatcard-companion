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
                    'debit' => '/payment',
                ],
            ],
            'wipay' => [
                'staging'    => 'https://wipayacc.worldline.nl',
                'production' => 'https://wipay.worldline.nl',
                'endpoints'  => [
                    'debit' => '/api/2.0/json/debit',
                ],
            ],
        ],
        'webhook' => [
            'pos' => [
                'order'     => '/pos/webhook/<%id%>/<%store_id%>',
                'sub_order' => '/pos/webhook-sub/<%id%>/<%store_id%>',
            ],
        ],
        'returnUrl' => [
            'pos' => '/pos',
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

    'aws_url' => env('AWS_URL', 'https://eatcard.s3.eu-central-1.amazonaws.com/'),
];
```
