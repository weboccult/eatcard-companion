<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TableEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Table can\'t be empty.!');
    }
}
