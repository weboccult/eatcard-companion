<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class ReservationTypeInvalidException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation type  can\'t be empty or Must be All you can eat.!');
    }
}
