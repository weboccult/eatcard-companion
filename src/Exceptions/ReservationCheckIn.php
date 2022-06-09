<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationCheckIn extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation is already check-in');
    }
}
