<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintGeneratorEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Print Generator can\'t be empty.!');
    }
}
