<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static entityType($entityType)
 * @method static entityId($entityId)
 * @method static mailType($mailType)
 * @method static mailFromName($mailFromName)
 * @method static subject($subject)
 * @method static content($content)
 * @method static email($email)
 * @method static cc($cc)
 * @method static bcc($bcc)
 * @method static dispatch()
 *
 * @see \Weboccult\EatcardCompanion\Services\Core\EatcardEmail
 */
class EatcardEmail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'eatcard-email';
    }
}
