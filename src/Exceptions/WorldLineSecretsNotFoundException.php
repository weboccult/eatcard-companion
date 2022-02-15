<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class WorldLineSecretsNotFoundException extends EatcardException
{
    public function __construct()
    {
        parent::__construct('Secret files must be present at .'.public_path('worldline'));
    }
}
