<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class NoKitchenPrintForUntilException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('For until reservation, we need to print the kitchen print!');
    }
}
