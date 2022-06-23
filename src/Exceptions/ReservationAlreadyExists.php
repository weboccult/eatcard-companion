<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationAlreadyExists extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation already exists at current time on this table');
    }
}
