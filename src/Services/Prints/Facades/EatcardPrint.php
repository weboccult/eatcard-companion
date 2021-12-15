<?php

namespace Weboccult\EatcardCompanion\Services\Prints\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed toJson()
 * @method static EatcardPrint via(string $driver)
 * @mixin EatcardPrint
 * @return EatcardPrint
 * @package EatcardPrint
 */
class EatcardPrint extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'eatcard-print';
    }
}
