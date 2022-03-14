<?php

namespace Weboccult\EatcardCompanion\Services\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Table;
use Weboccult\EatcardCompanion\Services\Core\Untill as UntillCore;

/**
 * @method static self build(string $template, array $parameters)
 * @method static self store(Model $store)
 * @method static self table($table)
 * @method static self getActiveTableInfo()
 * @method static self getTableItemsInfo()
 * @method static self getPaymentsInfo()
 * @method static self closeOrder()
 * @method static self createOrder(array $items)
 * @method static self setCredentials()
 * @method static self setTableNumber()
 * @method static self setPaymentId($paymentId)
 * @method static self setPersons($persons)
 * @method static self setFirstName($firstName)
 * @method static self getReturnCode($requestName, $response)
 * @method static self getReturnMessage($requestName, $response)
 * @method static self getOutput($requestName, $responsePath, $response)
 * @method static mixed dispatch()
 *
 * @see UntillCore
 *
 * @author Darshit Hedpara
 */
class Untill extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return UntillCore::class;
    }
}
