<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Traits;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * @description Common Setters
 *
 * @method void setDate(string $date)
 * @method string getDate()
 * @method void setPayload(array $payload)
 * @method array getPayload()
 * @method void setMonth(string $month)
 * @method string getMonth()
 * @method void setYear(string $year)
 * @method string getYear()
 * @method void setStoreId(string $storeId)
 * @method string getStoreId()
 * @method void setRevenueMethod(string $revenueMethod)
 * @method string getRevenueMethod()
 * @method void setCommonRules(array $array)
 * @method array getCommonRules()
 * @method void setGeneratorSpecificRules(array $array)
 * @method array getGeneratorSpecificRules()
 *
 * @author Darshit Hedpara
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
