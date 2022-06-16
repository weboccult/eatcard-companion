<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class UserEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('User id is required');
    }
}
