<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks;

use Exception;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\WebhookActionNotSupportedException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MollieDineInOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MollieDineInOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderCancelRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\MollieGiftCardSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\MollieGiftCardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\MultiSafeGiftCardCancelRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\MultiSafeGiftCardSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\MultiSafeGiftCardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard\WorldLineGetFinalPaymentStatusAction;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Admin\WorldLineWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Kiosk\CcvKioskOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation\MollieReservationSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation\MultiSafeReservationCancelRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation\MultiSafeReservationSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation\MultiSafeReservationWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Sms\TwilioSmsWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation\MollieReservationWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway\MollieTakeawayOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway\MollieTakeawayOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway\MultiSafeTakeawayOrderCancelRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway\MultiSafeTakeawayOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway\MultiSafeTakeawayOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\CashTicketsWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\CcvTicketsWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\WorldLineTicketsWebhook;

/**
 * @author Darshit Hedpara
 */
class EatcardWebhook
{
    private static ?EatcardWebhook $instance = null;

    private static BaseWebhook $webhook;

    /** @var string|int|null */
    private static $storeId;

    /** @var string|int|null */
    private static $orderId;

    /** @var string */
    private static $orderType;

    /** @var array|null */
    private static ?array $payload;

    /** @var string|int|null */
    private static $reservationId;

    /** @var string|int|null */
    private static $paymentId;

    /** @var string|int|null */
    private static $giftCardPurchaseOrderId;

    /** @var string */
    private static string $domainUrl = '';

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
    public static function action(string $webhook): self
    {
        if (class_exists($webhook)) {
            static::$webhook = new $webhook();
        } else {
            throw new ClassNotFoundException($webhook);
        }

        return static::getInstance();
    }

    /**
     * @param array $payload
     *
     * @return static
     */
    public static function payload(array $payload): self
    {
        static::$payload = $payload;

        return static::getInstance();
    }

    /**
     * @param string|int $storeId
     *
     * @return static
     */
    public static function setStoreId($storeId): self
    {
        static::$storeId = $storeId;

        return static::getInstance();
    }

    /**
     * @param string|int $orderId
     *
     * @return static
     */
    public static function setOrderId($orderId): self
    {
        static::$orderId = $orderId;

        return static::getInstance();
    }

    /**
     * @param string $orderType
     *
     * @return static
     */
    public static function setOrderType(string $orderType): self
    {
        static::$orderType = $orderType;

        return static::getInstance();
    }

    /**
     * @param string|int $giftPurchaseOrderId
     *
     * @return static
     */
    public static function setGiftPurchaseOrderId($giftPurchaseOrderId): self
    {
        static::$giftCardPurchaseOrderId = $giftPurchaseOrderId;

        return static::getInstance();
    }

    /**
     * @param string|int $reservationId
     *
     * @return static
     */
    public static function setReservationId($reservationId): self
    {
        static::$reservationId = $reservationId;

        return static::getInstance();
    }

    /**
     * @param string|int $paymentId
     *
     * @return static
     */
    public static function setPaymentId($paymentId): self
    {
        static::$paymentId = $paymentId;

        return static::getInstance();
    }

    /**
     * @param string $domainUrl
     *
     * @return static
     */
    public static function setDomainUrl(string $domainUrl): self
    {
        static::$domainUrl = $domainUrl;

        return static::getInstance();
    }

    /**
     * @return mixed
     */
    public static function dispatch()
    {
        $class = get_class(static::$webhook);
        switch ($class) {
            /* Takeaway Webhook and Redirects*/
            case MollieTakeawayOrderWebhook::class:
            case MollieTakeawayOrderSuccessRedirect::class:
            case MultiSafeTakeawayOrderWebhook::class:
            case MultiSafeTakeawayOrderSuccessRedirect::class:
            case MultiSafeTakeawayOrderCancelRedirect::class:
            /* DineIn Webhook and Redirects*/
            case MollieDineInOrderWebhook::class:
            case MollieDineInOrderSuccessRedirect::class:
            case MultiSafeDineInOrderWebhook::class:
            case MultiSafeDineInOrderSuccessRedirect::class:
            case MultiSafeDineInOrderCancelRedirect::class:
                static::$webhook->setOrderId(static::$orderId)->setStoreId(static::$storeId);
                if (! empty(static::$domainUrl)) {
                    static::$webhook->setDomainUrl(static::$domainUrl);
                }
                break;
            /* Kiosk Webhook */
            case CcvKioskOrderWebhook::class:
                static::$webhook->setOrderId(static::$orderId)->setStoreId(static::$storeId);
                break;
            /* Reservation Iframe Webhook and Redirects*/
            case MollieReservationWebhook::class:
            case MollieReservationSuccessRedirect::class:
            case MultiSafeReservationWebhook::class:
            case MultiSafeReservationSuccessRedirect::class:
            case MultiSafeReservationCancelRedirect::class:
                static::$webhook->setReservationId(static::$reservationId)->setStoreId(static::$storeId);
                break;
            /* Giftcard Iframe Webhook and Redirects*/
            case MollieGiftCardWebhook::class:
            case MollieGiftCardSuccessRedirect::class:
            case MultiSafeGiftCardWebhook::class:
            case MultiSafeGiftCardSuccessRedirect::class:
            case MultiSafeGiftCardCancelRedirect::class:
                static::$webhook->setGiftCardPurchaseOrderId(static::$giftCardPurchaseOrderId)->setStoreId(static::$storeId);
                break;
            /* Twilio SMS Webhook*/
            case TwilioSmsWebhook::class:
                // only payload is required, which is already being set before running handle method.
                break;
            /* Wipay Global Webhook */
            case WorldLineWebhook::class:
            case WorldLineGetFinalPaymentStatusAction::class:
                static::$webhook->setOrderType(static::$orderType)->setOrderId(static::$orderId)->setStoreId(static::$storeId);
                // only payload is required, which is already being set before running handle method.
                break;

            case WorldLineTicketsWebhook::class:
            case CcvTicketsWebhook::class:
            case CashTicketsWebhook::class:
                static::$webhook->setOrderType(static::$orderType)
                                ->setReservationId(static::$reservationId)
                                ->setPaymentId(static::$paymentId)
                                ->setStoreId(static::$storeId);
                // only payload is required, which is already being set before running handle method.
                break;
            default:
                throw new WebhookActionNotSupportedException($class);
        }

        return static::$webhook->setPayload(static::$payload)->handle();
    }
}
