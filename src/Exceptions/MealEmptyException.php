<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 */
class MealEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Meal not found');
    }
}
