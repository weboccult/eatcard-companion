<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class FromTimeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('From time is required and can\'t be empty');
    }
}
