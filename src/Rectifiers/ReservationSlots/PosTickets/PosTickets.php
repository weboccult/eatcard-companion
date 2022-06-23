<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\PosTickets;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\BaseReservationSlots;

class PosTickets extends BaseReservationSlots
{
    public $systemType = SystemTypes::POS;
}
