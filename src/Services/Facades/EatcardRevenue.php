<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\EatcardRevenue as EatcardRevenueCore;

/**
 * @method static mixed generate()
 *
 * @mixin EatcardRevenueCore
 *
 * @return EatcardRevenueCore
 *
 * @see EatcardRevenueCore
 */
class EatcardRevenue extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'eatcard-revenue';
    }
}
