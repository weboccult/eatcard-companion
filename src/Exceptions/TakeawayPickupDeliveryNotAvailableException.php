<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TakeawayPickupDeliveryNotAvailableException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Takeaway pickup and delivery not available.!');
    }
}
