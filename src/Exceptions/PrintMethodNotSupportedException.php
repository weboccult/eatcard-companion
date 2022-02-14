<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintMethodNotSupportedException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('This print method not supported for this generator.!');
    }
}
