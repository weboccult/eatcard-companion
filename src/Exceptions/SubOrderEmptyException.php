<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class SubOrderEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Sub Order can\'t be empty.!');
    }
}
