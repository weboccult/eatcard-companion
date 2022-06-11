<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationExpired extends EatcardException
{
    public function __construct()
    {
        parent::__construct('This reservation has already expired');
    }
}
