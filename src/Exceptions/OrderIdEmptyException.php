<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class OrderIdEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reference Order Id  must be provided and can\'t be empty.!');
    }
}
