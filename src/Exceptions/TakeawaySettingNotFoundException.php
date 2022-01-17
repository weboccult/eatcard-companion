<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class TakeawaySettingNotFoundException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Takeaway Settings not found.!');
    }
}
