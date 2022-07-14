<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class ReservationAmountLessThenZero extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Please contact to store person for refund');
    }
}