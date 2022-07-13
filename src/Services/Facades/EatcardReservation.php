<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\EatcardReservation as EatcardReservationCore;

/**
 * @method static payload(array $cart)
 * @method static processor(string $processor)
 * @method static system(string $system)
 * @method static dispatch()
 *
 * @mixin EatcardReservationCore
 *
 * @return EatcardReservationCore
 */
class EatcardReservation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return EatcardReservationCore::class;
    }
}
