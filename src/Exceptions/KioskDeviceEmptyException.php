<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class KioskDeviceEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Kiosk Device can\'t be empty.!');
    }
}
