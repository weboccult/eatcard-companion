<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class MealIdEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Meal id can\t be empty');
    }
}
