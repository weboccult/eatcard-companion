<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate;

use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\KioskTicketsUpdate\KioskTicketsUpdate;

class EatcardReservationUpdate
{
    private static ?EatcardReservationUpdate $instance = null;

    protected static BaseReservationUpdate $action;

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

    /** @var string|int|null */
    protected static $storeId;

    /** @var string|null */
    protected static ?string $date;

    /** @var string|int|null */
    protected static $mealId;

    protected static $reservationId;

    protected static $dineInPriceId;

    protected static $deviceId;

    protected static $system;

    /** @var array|null */
    protected static ?array $payload;

    public static function setPayload(array $payload): self
    {
        static::$payload = $payload;

        return static::getInstance();
    }

    public static function setSystem($system): self
    {
        static::$system = $system;

        return static::getInstance();
    }

    /**
     * @param string $action
     * @param array $data
     *
     * @return static
     */
    public static function action(string $action, array $data = []): self
    {
        if (class_exists($action)) {
            static::$action = new $action();
        } else {
            throw new ClassNotFoundException($action);
        }

        return static::getInstance();
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public static function dispatch()
    {
        $class = get_class(static::$action);
        switch ($class) {
            case KioskTicketsUpdate::class:
            break;
            default:
        ////                throw new WebhookActionNotSupportedException($class);
        }

        return static::$action->setSystem(static::$system)
            ->setPayload(static::$payload)
            ->dispatch();
    }
}
