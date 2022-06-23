<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\Traits;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * @description Common Setters
 *
 * @method void setPrintGenerator(string $printGenerator)
 * @method string getPrintGenerator()
 * @method void setPrintMethod(string $printMethod)
 * @method string getPrintMethod()
 * @method void setPayload(array $payload)
 * @method array getPayload()
 * @method void setOrderType(string $orderType)
 * @method string getOrderType()
 * @method void setPrintType(string $printType)
 * @method string getPrintType()
 * @method void setSystemType(string $systemType)
 * @method string getSystemType()
 * @method void setPayloadRequestDetails(array $requestDetails)
 * @method void setDumpDieValue($data)
 * @method array getPayloadRequestDetails()
 * @method void setCommonRules(array $array)
 * @method array getCommonRules()
 * @method void setGeneratorSpecificRules(array $array)
 * @method array getGeneratorSpecificRules()
 */
trait MagicAccessors
{
    /**
     * @param $method
     * @param $args
     *
     * @return $this|void
     */
    public function __call($method, $args)
    {
        if (! preg_match('/(?P<accessor>set|get)(?P<property>[A-Z][a-zA-Z0-9]*)/', $method, $match) || ! property_exists(__CLASS__, $match['property'] = lcfirst($match['property']))) {
            throw new BadMethodCallException(sprintf("'%s' does not exist in '%s'.", $method, get_class((object) __CLASS__)));
        }
        switch ($match['accessor']) {
            case 'get':
                return $this->{$match['property']};
            case 'set':
                if (! $args) {
                    throw new InvalidArgumentException($method);
                }
                $this->{$match['property']} = $args[0];

                return $this;
        }
    }
}
