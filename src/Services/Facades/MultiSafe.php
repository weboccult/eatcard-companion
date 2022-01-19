<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\MultiSafe as MultiSafeCore;

/**
 * @return MultiSafeCore
 * @mixin MultiSafeCore
 *
 * @see MultiSafeCore
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::getMode
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::setMode
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::getPaymentUrl
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::setPaymentUrl
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::getApiKey
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::setApiKey
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::getIssuers
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::postOrder
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::getOrder
 * @see \Weboccult\EatcardCompanion\Services\Core\MultiSafe::refundOrder
 *
 * @method static string getMode()
 * @method static MultiSafe setMode(string $mode)
 * @method static string getPaymentUrl()
 * @method static MultiSafe setPaymentUrl(string $paymentUrl)
 * @method static string getApiKey()
 * @method static MultiSafe setApiKey(string $apiKey)
 * @method static array|bool|mixed getIssuers()
 * @method static array|null postOrder($data)
 * @method static array|null|mixed getOrder($orderId)
 * @method static array|null|mixed refundOrder($orderId, $data)
 *
 * @author Darshit Hedpara
 */
class MultiSafe extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'multi-safe';
    }
}
