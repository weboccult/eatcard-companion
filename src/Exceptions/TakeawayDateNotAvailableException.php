<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TakeawayDateNotAvailableException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Takeaway Pickup & Delivery not available.!');
    }
}
