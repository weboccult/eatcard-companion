<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class SubOrderPrintSettingsDisableException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('SubOrder print settings is disable!');
    }
}
