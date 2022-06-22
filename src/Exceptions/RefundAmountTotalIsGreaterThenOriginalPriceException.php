<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class RefundAmountTotalIsGreaterThenOriginalPriceException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Refund amount is greater then original total');
    }
}
