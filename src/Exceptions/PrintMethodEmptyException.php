<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintMethodEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Print Method can\'t be empty.!');
    }
}
