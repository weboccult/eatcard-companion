<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintRequestTypeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Print Request Type must be provided and can\'t be empty.!');
    }
}
