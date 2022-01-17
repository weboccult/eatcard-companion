<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TakeawayOrderDateTimeNotValidException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Takeaway Order Date and Time is not valid.!');
    }
}
