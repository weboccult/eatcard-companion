<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationNoShow extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation is no-show please contact store person');
    }
}
