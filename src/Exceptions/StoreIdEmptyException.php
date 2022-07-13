<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class StoreIdEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Store id can\t be empty');
    }
}
