<?php

namespace Weboccult\EatcardCompanion\Services\Common\ThirdPartyOrders;

use Exception;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;

/**
 * @author Darshit Hedpara
 */
class EatcardThirdPartyOrder
{
    static private ?EatcardThirdPartyOrder $instance = null;

    static private ThirdPartyOrders $processor;
    static private array $data = [];

    /**
     * @return null|static
     */
    static function getInstance(): ?EatcardThirdPartyOrder
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @throws Exception
     */
    public static function run(string $processor, $data = []): self
    {
        if (class_exists($processor)) {
            static::$processor = new $processor();
        }
        else {
            throw new ClassNotFoundException($processor);
        }
        return static::getInstance();
    }

    public static function setData(array $data): self
    {
        static::$data = $data;
    }

    /**
     * @return mixed
     */
    public static function dispatch()
    {
        return static::$processor->handle(static::$data);
    }
}

// usage
//try {
//    EatcardThirdPartyOrder::
//        run(Deliveroo::class)
//        ->setData([])
//        ->dispatch();
//}
//catch (Exception $e) {
//}
