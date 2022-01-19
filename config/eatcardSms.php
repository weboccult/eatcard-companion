<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following gateway to use.
    | You can switch to a different driver at runtime.
    |
    */
    'default' => 'file',

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | These are the list of drivers to use for this package.
    | You can change the name. Then you'll have to change
    | it in the map array too.
    |
    */
    'drivers' => [
        'file'   => [
            'log_level' => 'info',
            // alert | critical | debug | emergency | error | info | log | notice | warning | write
        ],
        'twilio' => [
            'sid'   => 'Your SID',
            'token' => 'Your Token',
            'from'  => 'Your Default From Number',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Class Maps
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to extend
    | App\Services\Sms\Abstracts\Driver in your driver.
    |
    */
    'map'     => [
        'file'   => \Weboccult\EatcardCompanion\Services\Common\Sms\Drivers\File::class,
        'twilio' => \Weboccult\EatcardCompanion\Services\Common\Sms\Drivers\Twilio::class,
    ],
];
