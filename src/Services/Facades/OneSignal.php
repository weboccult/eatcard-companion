<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\OneSignal as OneSignalCore;

/**
 * @see \Weboccult\EatcardCompanion\Services\Core\OneSignal::sendPushNotification
 *
 * @method static bool|void sendPushNotification($notification)
 *
 * @mixin OneSignalCore
 *
 * @return OneSignalCore
 *
 * @see OneSignalCore
 *
 * @author Darshit Hedpara
 */
class OneSignal extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'one-signal';
    }
}
