<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class PersonEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Reservation Person can\'t be empty or not provided.!');
    }
}
