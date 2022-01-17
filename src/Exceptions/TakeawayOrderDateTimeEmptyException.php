<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TakeawayOrderDateTimeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Takeaway Order Date and Time can\'t be empty.!');
    }
}
