<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintTypeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Print Type can\'t be empty.!');
    }
}
