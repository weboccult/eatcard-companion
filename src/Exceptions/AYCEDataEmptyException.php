<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class AYCEDataEmptyException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('AYCE data can\'t be empty.!');
    }
}
