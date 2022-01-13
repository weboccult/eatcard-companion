<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class SystemTypeEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('System Type can\'t be empty.!');
    }
}