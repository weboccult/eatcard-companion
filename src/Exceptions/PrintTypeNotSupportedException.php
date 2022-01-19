<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintTypeNotSupportedException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('This print type not supported for this generator.!');
    }
}
