<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\EatcardOrder as EatcardOrderCore;

/**
 * @method static cart(array $cart)
 * @method static payload(array $cart)
 * @method static processor(string $processor)
 * @method static system(string $system)
 * @method static dispatch()
 *
 * @mixin EatcardOrderCore
 *
 * @return EatcardOrderCore
 */
class EatcardOrder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'eatcard-order';
    }
}
