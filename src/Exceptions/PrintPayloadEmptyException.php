<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PrintPayloadEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Print Payload can\'t be empty.!');
    }
}
