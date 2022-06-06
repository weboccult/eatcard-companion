<?php

/**
 * @description To manage all the settings and constants from this config file
 *
 * @author Darshit Hedpara
 */

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
        | @see Weboccult\EatcardCompanion\Enums\LoggerTypes
        */

        'driver'  => env('COMPANION_LOGGER_DRIVER', 'FILE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | You can enable localization using enable_translation flag.
    |
    */
    'enable_translation'       => env('COMPANION_TRANSLATION_STATUS', true),
    'enable_print_translation' => env('COMPANION_PRINT_TRANSLATION_STATUS', false),

    'enable_legacy_print' => env('COMPANION_LEGACY_PRINT', true),

    /*
    |--------------------------------------------------------------------------
    | System Endpoints
    |--------------------------------------------------------------------------
    |
    | Different system's endpoint will be managed from here
    |
    */
    'system_endpoints' => [
        'admin'            => env('COMPANION_ADMIN_ENDPOINT', 'http://eatcard-admin.local'),
        'pos'              => env('COMPANION_POS_ENDPOINT', 'http://eatcard-pos.local'),
        'takeaway'         => env('COMPANION_TAKEAWAY_ENDPOINT', 'http://eatcard-takeaway.local'),
        'kiosk'            => env('COMPANION_KIOSK_ENDPOINT', 'http://eatcard-kiosk.local'),
        'dine_in'          => env('COMPANION_DINE_IN_ENDPOINT', 'http://eatcard-dine_in-api.local'),
        'dine_in_frontend' => env('COMPANION_DINE_FRONTEND_ENDPOINT', 'http://eatcard-dine_in.local'),
        'kiosktickets'    => env('COMPANION_KIOSK_TICKET_ENDPOINT', 'http://eatcard-kiosk.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed webhook endpoint
    |--------------------------------------------------------------------------
    |
    | Here you can set ngRok or any third-party library to
    | expose your local project, so you can receive webhooks
    |
    | Note : use http instead of https because,
    | In your local machine you may not have SSL certificates and webhook might not work.
    */
    'exposed_webhook' => [
        'settings' => [
            /*
             *  If you are testing / working on local machine then,
             *  you may want to set exclude_webhook setting (which is available in payment section) to 'false'.
             *
             *  Some payment gateways are not allowing local urls as webhook endpoint.
             *  E.g. Mollie payment gateway
             *
             *  So you may want to set enable_exposed_webhook setting to true then,
             *  You can receive webhook from the payment gateways,
             *  Or you want mimic the behaviour of webhook same as production or staging environment.
             *
             *  Note :
             *   1. exclude_webhook has a higher priority then exposed_webhook settings.
             *   2. Please set value false, In case you're in development mode.
             *      Or set exposed_webhook URL using ngrok etc third-party and then set value true.
             *
             */
            'enable_exposed_webhook' => env('COMPANION_ENABLE_EXPOSED_WEBHOOK', false),
        ],
        'admin'    => env('COMPANION_EXPOSED_ADMIN_WEBHOOK', 'http://xyz.ngrok.com'),
        'pos'      => env('COMPANION_EXPOSED_POS_WEBHOOK', 'http://xyz.ngrok.com'),
        'takeaway' => env('COMPANION_EXPOSED_TAKEAWAY_WEBHOOK', 'http://xyz.ngrok.com'),
        'kiosk'    => env('COMPANION_EXPOSED_KIOSK_WEBHOOK', 'http://xyz.ngrok.com'),
        'dine_in'  => env('COMPANION_EXPOSED_DINE_IN_WEBHOOK', 'http://xyz.ngrok.com'),
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
            'api_url'               => env('COMPANION_ONE_SIGNAL_API_URL', 'https://onesignal.com/api/v1'),
            'create_device_url'     => env('COMPANION_ONE_SIGNAL_CREATE_DEVICE_URL', '/players/<%onesignal_id%>?app_id=<%app_id%>'),
            'send_notification_url' => env('COMPANION_ONE_SIGNAL_CREATE_DEVICE_URL', '/notifications'),
            'app_id'                => env('COMPANION_ONE_SIGNAL_APP_ID', ''),
            'rest_api_key'          => env('COMPANION_ONE_SIGNAL_REST_API_KEY', ''),
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
        'settings' => [
            /*
             *  If you are testing / working on local machine then,
             *  you may want to set exclude_webhook setting to 'true'.
             *
             *  It will remove the key-value pair while creating payment.
             *  some payment gateways are not allowing local urls as webhook endpoint.
             *  E.g. Mollie payment gateway
             *
             *  If you want to use exposed webhook then you may not want to set false to exclude_webhook.
             *  As exclude_webhook has a higher priority then exposed_webhook settings.
             */
            'exclude_webhook' => env('COMPANION_EXCLUDE_WEBHOOK', false),
        ],
        'gateway'  => [
            'ccv'       => [
                'staging'    => 'http://vpos-test.jforce.be/vpos/api/v1',
                'production' => 'https://redirect.jforce.be/api/v1',
                'endpoints'  => [
                    'createOrder' => '/payment',
                    'fetchOrder'  => '/transaction?reference=',
                ],
                'webhook'    => [
                    'pos'   => [
                        'order'     => '/pos/webhook/<%id%>/<%store_id%>',
                        'sub_order' => '/pos/webhook-sub/<%id%>/<%store_id%>',
                        'reservation' => '/pos/webhook-reservation/<%id%>/<%store_id%>',
                    ],
                    'kiosk' => [
                        'order' => '/kiosk/webhook/<%id%>/<%store_id%>',
                    ],
                    'kiosk-tickets' => [
                        'reservation' => '/ccv/webhook/<%id%>/<%store_id%>',
                    ],
                ],
                'returnUrl'  => [
                    'pos'   => '/pos',
                    'kiosk' => '/<%device_id%>',
                    'kiosk-tickets' => '/<%device_id%>',
                ],
            ],
            'wipay'     => [
                'staging'    => 'https://wipayacc.worldline.nl',
                'production' => 'https://wipay.worldline.nl',
                'endpoints'  => [
                    'createOrder' => '/api/2.0/json/debit',
                    'fetchOrderStatus' => '/api/2.0/json/status',
                ],
                'webhook'    => null,
                // No need, it will point to main domain directly.
            ],
            'multisafe' => [
                'mode'        => env('COMPANION_MULTISAFE_MODE', 'live'),
                'staging'     => 'https://testapi.multisafepay.com/v1/json',
                'production'  => 'https://api.multisafepay.com/v1/json',
                'endpoints'   => [
                    'paymentMethod' => '/gateways',
                    'issuer'        => '/issuers/IDEAL',
                    'createOrder'   => '/orders',
                    'getOrder'      => '/orders/<%order_id%>',
                    'refundOrder'   => '/orders/<%order_id%>/refunds',
                ],
                'webhook'     => [
                    'takeaway' => '/multisafe/takeaway/webhook/<%id%>/<%store_id%>',
                    'dine_in'  => '/webhook/multisafe/<%id%>/<%store_id%>',
                ],
                'redirectUrl' => [
                    'takeaway' => '/multisafe/takeaway/orders-success/<%id%>/<%store_id%>',
                    'dine_in'  => '/orders-success/multisafe/<%id%>/<%store_id%>',
                ],
                'cancelUrl'   => [
                    'takeaway' => '/multisafe/takeaway/cancel/<%id%>/<%store_id%>',
                    'dine_in'  => '/cancel/multisafe/<%id%>/<%store_id%>',
                ],
            ],
            'mollie'    => [
                'webhook'     => [
                    'takeaway' => '/webhook/<%id%>/<%store_id%>',
                    'dine_in'  => '/webhook/mollie/<%id%>/<%store_id%>',
                ],
                'redirectUrl' => [
                    'takeaway' => '/orders-success/<%id%>/<%store_id%>',
                    'dine_in'  => '/orders-success/mollie/<%id%>/<%store_id%>',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manage SMS settings
    |--------------------------------------------------------------------------
    |
    | Setting, URLs and more.
    |
    */
    'sms' => [
        'webhook' => [
            'admin' => '/sms/webhook/status-update',
        ],
    ],

    'third_party' => [
        'deliveroo' => [
            'url' => env('DELIVEROO_URL', null),
            'credential' => env('DELIVEROO_CREDENTIALS', null),
        ],
    ],

    'untill' => [
        'app_token' => env('COMPANION_UNTILL_APP_TOKEN', null),
        'app_name' => env('COMPANION_UNTILL_APP_NAME', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Other Endpoints
    |--------------------------------------------------------------------------
    |
    | Other endpoint will be managed from here
    |
    */

    'aws_url' => env('COMPANION_AWS_URL', 'https://eatcard.s3.REGION-X.amazonaws.com'),

    /*
    |--------------------------------------------------------------------------
    | Qr Setting
    |--------------------------------------------------------------------------
    |
    | Qr related setting can be manage form here
    |
    */

    'generate_qr' => [
        'size' => env('QR_SIZE', 300),
        'merge_image' => env('QR_MERGE_IMAGE', 'https://eatcard-stage.s3.eu-central-1.amazonaws.com/Eatcard_app_icon.png'),
        'format' => env('QR_FORMAT', 'png'),
        'destination_folder' => env('QR_DESTINATION_FOLDER', 'assets'),
    ],
];
