<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Services\Core\MultiSafe as MultiSafeCore;

/**
 * @return MultiSafeCore
 * @mixin MultiSafeCore
 *
 * @see MultiSafeCore
 * @see MultiSafeCore::getMode
 * @see MultiSafeCore::setMode
 * @see MultiSafeCore::getPaymentUrl
 * @see MultiSafeCore::setPaymentUrl
 * @see MultiSafeCore::getApiKey
 * @see MultiSafeCore::setApiKey
 * @see MultiSafeCore::getIssuers
 * @see MultiSafeCore::postOrder
 * @see MultiSafeCore::getOrder
 * @see MultiSafeCore::refundOrder
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
        return MultiSafeCore::class;
    }
}
