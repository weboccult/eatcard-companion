<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Traits;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * @description Common Setters
 *
 * @method string getSystem()
 * @method void setSystem(string $system)
 * @method array getCart()
 * @method void setCart(array $cart)
 * @method array getPayload()
 * @method void setPayload(array $cart)
 * @method mixed getCommonRules()
 * @method void setCommonRules(array $array)
 * @method array getSystemSpecificRules()
 * @method void setSystemSpecificRules(array $array)
 * @method mixed beforeCheckObserver(string $method)
 * @method mixed afterCheckObserver(string $method)
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
