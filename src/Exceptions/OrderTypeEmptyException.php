<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class OrderTypeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Order Type can\'t be empty.!');
    }
}
