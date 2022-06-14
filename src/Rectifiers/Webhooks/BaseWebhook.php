<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\OrderNotFoundException;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\eatcardPrint;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\PaymentDetail;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;

/**
 * @author Darshit Hedpara
 */
abstract class BaseWebhook
{
    /** @var string|int|null */
    public $orderId = null;

    /** @var string|int|null */
    public $orderType = 'order'; // sub_order

    /** @var string|int|null */
    public $storeId = null;

    /** @var string|int|null */
    public $giftCardPurchaseOrderId = null;

    /** @var string|int|null */
    public $reservationId = null;

    /** @var string|int|null */
    public $paymentId = null;

    /** @var array|null */
    public ?array $payload = null;

    /** @var Order|OrderHistory|null|object */
    protected $fetchedOrder = null;

    /** @var Store|null|object */
    protected $fetchedStore = null;

    /** @var StoreReservation|null|object */
    protected $fetchedReservation = null;

    /** @var StoreReservation|null|object */
    protected $fetchedPaymentDetails = null;

    /** @var GiftPurchaseOrder|null|object */
    protected $fetchedGiftPurchaseOrder = null;

    /** @var string */
    protected string $domainUrl = '';

    /**
     * @param int|string|null
     *
     * @return BaseWebhook
     */
    public function setStoreId($orderId): self
    {
        $this->storeId = $orderId;

        return $this;
    }

    /**
     * @param int|string|null $reservationId
     *
     * @return BaseWebhook
     */
    public function setReservationId($reservationId): self
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    /**
     * @param int|string|null $reservationId
     *
     * @return BaseWebhook
     */
    public function setPaymentId($paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * @param int|string|null $orderId
     *
     * @return BaseWebhook
     */
    public function setOrderId($orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @param string $orderType
     *
     * @return BaseWebhook
     */
    public function setOrderType(string $orderType): self
    {
        $this->orderType = $orderType;

        return $this;
    }

    /**
     * @param int|string|null $giftCardPurchaseOrderId
     *
     * @return BaseWebhook
     */
    public function setGiftCardPurchaseOrderId($giftCardPurchaseOrderId): self
    {
        $this->giftCardPurchaseOrderId = $giftCardPurchaseOrderId;

        return $this;
    }

    /**
     * @param array
     *
     * @return BaseWebhook
     */
    public function setPayload(array $data): self
    {
        $this->payload = $data;

        return $this;
    }

    /**
     * @param string $domainUrl
     *
     * @return BaseWebhook
     */
    public function setDomainUrl(string $domainUrl): self
    {
        $this->domainUrl = $domainUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    abstract public function handle();

    /**
     * @return Builder|Model|object|null
     */
    protected function fetchAndSetSubOrder($ssai)
    {
        return $this->fetchedOrder = SubOrder::where(['worldline_ssai' => $ssai])->firstOrFail();
    }

    /**
    }
     * @return Builder|Model|object|null
     */
    protected function fetchAndSetOrder()
    {
        $this->fetchedOrder = Order::with([
            'kiosk',
            'orderItems' => function ($q1) {
                $q1->with([
                    'product' => function ($q2) {
                        $q2->with([
                            'category',
                            'printers',
                        ]);
                    },
                ]);
            },
        ])->where('id', $this->orderId)->first();
        if (empty($this->fetchedOrder)) {
            $this->fetchedOrder = OrderHistory::with([
                'kiosk',
                'orderItems' => function ($q1) {
                    $q1->with([
                        'product' => function ($q2) {
                            $q2->with([
                                'category',
                                'printers',
                            ]);
                        },
                    ]);
                },
            ])->where('id', $this->orderId)->first();
        }
        if (empty($this->fetchedOrder)) {
            throw new OrderNotFoundException();
        }

        if (isset($this->fetchedOrder->parent_id) && ! empty($this->fetchedOrder->parent_id)) {
            $this->reservationId = $this->fetchedOrder->parent_id ?? null;
        }

        return $this->fetchedOrder;
    }

    protected function updateOrder(array $data)
    {
        $this->fetchedOrder->update($data);
        $this->fetchedOrder->refresh();
    }

    protected function updateReservation(array $data)
    {
        $this->fetchedReservation->update($data);
        $this->fetchedReservation->refresh();
    }

    protected function updateGiftCardPurchaseOrder(array $data)
    {
        $this->fetchedGiftPurchaseOrder->update($data);
        $this->fetchedGiftPurchaseOrder->refresh();
    }

    protected function fetchAndSetStore()
    {
        $this->fetchedStore = Store::with('store_manager', 'store_owner', 'sqs', 'notificationSetting', 'multiSafe')->findOrFail($this->storeId);
    }

    protected function fetchAndSetGiftCardPurchaseOrder()
    {
        $this->fetchedGiftPurchaseOrder = GiftPurchaseOrder::with('giftCard')->findOrFail($this->giftCardPurchaseOrderId);
    }

    protected function fetchAndSetReservation()
    {
        if (! empty($this->reservationId)) {
            $this->fetchedReservation = StoreReservation::with('meal', 'kiosk')->findOrFail($this->reservationId);
        }
    }

    protected function fetchAndSePaymentDetails()
    {
        if (! empty($this->paymentId)) {
            $this->fetchedPaymentDetails = PaymentDetail::query()->findOrFail($this->paymentId);
        }
    }

    protected function sendKioskOrderMailToOwner($status) {
	    $isSendEmail = false;
	    if ($status == 'success' && $this->fetchedStore->store_email
		    && filter_var($this->fetchedStore->store_email, FILTER_VALIDATE_EMAIL)
		    && ($this->fetchedStore->is_notification)
		    && (! $this->fetchedStore->notificationSetting
			    || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_dine_in_email))
	    ) {
		    $isSendEmail = true;
	    }

	    if ($status == 'failed' && $this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_cancel_payment_email) {
		    $isSendEmail = true;
	    }

	    if ($isSendEmail) {
		    try {
			    $content = eatcardPrint()
				    ->generator(PaidOrderGenerator::class)
				    ->method(PrintMethod::HTML)
				    ->type(PrintTypes::MAIN)
				    ->system(SystemTypes::KIOSK)
				    ->payload([
					    'order_id'          => ''.$this->fetchedOrder->id,
					    'takeawayEmailType' => 'owner',
				    ])
				    ->generate();
			    $translatedSubject = __companionTrans('takeaway.takeaway_order_owner_mail_sub_subject');
			    eatcardEmail()
				    ->entityType('takeaway_user_email')
				    ->entityId($this->fetchedOrder->id)
				    ->email($this->fetchedStore->store_email)
				    ->mailType('Takeaway owner email')
				    ->mailFromName($this->fetchedStore->store_name)
				    ->subject($translatedSubject)
				    ->content($content)
				    ->dispatch();
			    updateEmailCount('success');
			    companionLogger('KIOSK order create mail success', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
		    } catch (Exception | Throwable $e) {
			    updateEmailCount('error');
			    companionLogger('KIOSK order create mail error', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
		    }
	    }
    }


}
