<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\EatcardPrint as EatcardPrintCore;

/**
 * @method static mixed toJson()
 *
 * @mixin EatcardPrintCore
 *
 * @return EatcardPrintCore
 *
 * @see EatcardPrintCore
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
