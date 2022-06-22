<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\KioskTickets;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\BaseReservationSlots;

class KioskTicketsTableAssign extends BaseReservationSlots
{
    public $systemType = SystemTypes::CRON;

    public function __construct()
    {
        parent::__construct();
    }
}
