<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders;

use Exception;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;

/**
 * @author Darshit Hedpara
 */
class EatcardThirdPartyOrder
{
    private static ?EatcardThirdPartyOrder $instance = null;

    private static ThirdPartyOrders $action;
    private static array $data = [];

    /**
     * @return null|static
     */
    public static function getInstance(): ?self
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @throws Exception
     */
    public static function action(string $action, $data = []): self
    {
        if (class_exists($action)) {
            static::$action = new $action();
        } else {
            throw new ClassNotFoundException($action);
        }

        return static::getInstance();
    }

    public static function setData(array $data): self
    {
        static::$data = $data;

        return static::getInstance();
    }

    /**
     * @return mixed
     */
    public static function dispatch()
    {
        return static::$action->handle(static::$data);
    }
}

// usage
//try {
//    EatcardThirdPartyOrder::
//        action(Deliveroo::class)
//        ->setData([])
//        ->dispatch();
//}
//catch (Exception $e) {
//}
