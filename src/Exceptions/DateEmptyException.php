<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class DateEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Date can\t be empty');
    }
}
