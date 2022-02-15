<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class RevenueTypeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Revenue Type  can\'t be empty.!');
    }
}
