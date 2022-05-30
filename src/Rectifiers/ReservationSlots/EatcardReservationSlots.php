<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationSlots;

class EatcardReservationSlots
{
    private static ?EatcardReservationSlots $instance = null;

    protected static BaseReservationSlots $reservationSlots;

    /**
     * @return null|static
     */
    public static function getInstance(): ?self
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
