<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\EatcardPrint as EatcardPrintCore;

/**
 * @method static mixed generate()
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
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return EatcardPrintCore::class;
    }
}
