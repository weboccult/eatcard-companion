<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class DuplicateCartIdException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Duplicate cart id is not allowed in the payload.!');
    }
}
