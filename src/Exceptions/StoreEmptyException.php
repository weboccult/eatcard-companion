<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class StoreEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Store can\'t be empty.!');
    }
}
