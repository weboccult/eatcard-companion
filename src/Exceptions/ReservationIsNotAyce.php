<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationIsNotAyce extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Not valid reservation, only all you eat reservation accept');
    }
}
