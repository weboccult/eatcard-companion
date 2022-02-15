<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class SaveOrderEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Save Order can\'t be empty.!');
    }
}
