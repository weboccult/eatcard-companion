<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class RevenuePayloadEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Revenue Payload  can\'t be empty.!');
    }
}
