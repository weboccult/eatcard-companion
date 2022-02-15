<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class OrderItemsNotFoundException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Order Items not found in your cart.!');
    }
}
