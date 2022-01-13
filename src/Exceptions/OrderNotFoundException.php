<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class OrderNotFoundException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reference Order not found .!');
    }
}
