<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class ReservationDateEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation date can\'t be empty or invalid !');
    }
}
