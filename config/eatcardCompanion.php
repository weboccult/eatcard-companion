<?php

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
        'driver' => env('COMPANION_LOGGER_DRIVER', Weboccult\EatcardCompanion\Enums\LoggerTypes::FILE),
    ],
];
