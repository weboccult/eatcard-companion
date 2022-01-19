<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static test()
 * @method static send($message)
 * @method static to($recipients)
 * @method static via($driver)
 * @method static responsible($modelOrId)
 * @method static type($type)
 * @method static channel($channel)
 * @method static storeId($Id)
 * @method static dispatch()
 * @method static getDriverInstance()
 *
 * @see \Weboccult\EatcardCompanion\Services\Core\EatcardSms
 */
class EatcardSms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'eatcard-sms';
    }
}
