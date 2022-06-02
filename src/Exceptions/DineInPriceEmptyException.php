<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class DineInPriceEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('DineIn Price data can\'t be empty or invalid !');
    }
}
