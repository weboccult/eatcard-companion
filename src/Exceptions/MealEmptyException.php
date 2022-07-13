<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class MealEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Meal can\'t be empty.!');
    }
}
