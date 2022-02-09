<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class RevenueParametersEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Revenue Parameter Date Or Month,Year  can\'t be empty.!');
    }
}
