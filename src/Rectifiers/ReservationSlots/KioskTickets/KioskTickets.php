<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\KioskTickets;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\BaseReservationSlots;

class KioskTickets extends BaseReservationSlots
{
    public $systemType = SystemTypes::KIOSKTICKETS;
}
