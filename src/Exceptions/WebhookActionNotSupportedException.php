<?php

namespace Weboccult\EatcardCompanion\Exceptions;

use Weboccult\EatcardCompanion\Exceptions\Core\EatcardException;

/**
 * Class Not Found Exception.
 *
 * @author Darshit Hedpara
 */
class WebhookActionNotSupportedException extends EatcardException
{
    /**
     * @param string $class
     */
    public function __construct($class)
    {
        parent::__construct(sprintf('Webhook Action %s not supported.', $class));
    }
}
