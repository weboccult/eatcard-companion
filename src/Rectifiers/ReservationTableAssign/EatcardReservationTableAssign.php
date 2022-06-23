<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign;

use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\KioskTickets\KioskTickets;

class EatcardReservationTableAssign
{
    private static ?EatcardReservationTableAssign $instance = null;

    protected static BaseReservationTableAssign $action;

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

    /** @var string|int|null */
    protected static $reservationId;

    /** @var string|null */
    protected static ?string $date;

    /** @var string|int|null */
    protected static $mealId;

    /** @var array|null */
    protected static ?array $payload;

    public static function setPayload(array $payload): self
    {
        static::$payload = $payload;

        return static::getInstance();
    }

    /**
     * @param $storeId
     *
     * @return static
     */
    public static function setStoreId($storeId): self
    {
        static::$storeId = $storeId;

        return static::getInstance();
    }

    /**
     * @param $reservationId
     *
     * @return static
     */
    public static function setReservationId($reservationId): self
    {
        static::$reservationId = $reservationId;

        return static::getInstance();
    }

    /**
     * @param $date
     *
     * @return static
     */
    public static function setDate($date): self
    {
        static::$date = $date;

        return static::getInstance();
    }

    /**
     * @param $mealId
     *
     * @return static
     */
    public static function setMealId($mealId): self
    {
        static::$mealId = $mealId;

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
            case KioskTickets::class:
            break;
            default:
//                throw new WebhookActionNotSupportedException($class);
        }

        return static::$action
            ->setStoreId(static::$storeId)
            ->setReservationId(static::$reservationId)
            ->setMealId(static::$mealId)
            ->setDate(static::$date)
            ->setPayload(static::$payload)
            ->dispatch();
    }
}
