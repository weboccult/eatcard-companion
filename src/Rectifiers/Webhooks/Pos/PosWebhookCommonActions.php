<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Pos;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis as LRedis;
use Throwable;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\eatcardPrint;
use function Weboccult\EatcardCompanion\Helpers\sendAppNotificationHelper;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;

/**
 * @mixin BaseWebhook
 *
 * @author Darshit Hedpara
 */
trait PosWebhookCommonActions
{
    public function applyCouponLogic()
    {
        $coupon_purchase = GiftPurchaseOrder::query()->where('id', $this->fetchedOrder->gift_purchase_id)->first();
        if ($coupon_purchase) {
            if ($coupon_purchase->is_multi_usage == 1) {
                $coupon_purchase->update(['remaining_price' => $coupon_purchase->remaining_price - $this->fetchedOrder->coupon_price]);
            } else {
                $coupon_purchase->update(['remaining_price' => 0]);
            }
        }
    }

    /**
     * @return array|void
     */
    public function sendNotifications()
    {
        $orderArray = $this->fetchedOrder->toArray();
        $is_notification = 0;
        if (($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting->is_dine_in_notification))) {
            $is_notification = 1;
            $data = sendAppNotificationHelper($orderArray, $this->fetchedStore);
            if (isset($data['exception'])) {
                return $data;
            }
        }
        $takeaway_data = [
            'orderDate'       => $this->fetchedOrder->order_date,
            'is_notification' => $is_notification,
        ];
        $socket_data = sendWebNotification($this->fetchedStore, $this->fetchedOrder, $takeaway_data);
        $socket_data['type'] = 'order';
        $socket_data['kiosk_id'] = $this->fetchedOrder->kiosk_id;
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }
    }

    public function sendTakeawayOwnerEmail()
    {
        if ($this->fetchedStore->store_email && filter_var($this->fetchedStore->store_email, FILTER_VALIDATE_EMAIL) && ($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_takeaway_email))) {
            try {
                $content = eatcardPrint()
                    ->generator(PaidOrderGenerator::class)
                    ->method(PrintMethod::HTML)
                    ->type(PrintTypes::MAIN)
                    ->system(SystemTypes::TAKEAWAY)
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
                companionLogger('Takeaway order create mail success', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Takeaway order create mail error', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }
    }

    public function sendPrintJsonToSQS()
    {
        // Todo : get print JSON data from EatcardPrint Service
        $printRes = [];
        if (! empty($printRes)) {
            config([
                'queue.connections.sqs.region' => $this->fetchedStore->sqs->sqs_region,
                'queue.connections.sqs.queue'  => $this->fetchedStore->sqs->sqs_queue_name,
                'queue.connections.sqs.prefix' => $this->fetchedStore->sqs->sqs_url,
            ]);
            Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->fetchedStore->sqs->sqs_queue_name);
        }
    }

    public function checkoutReservation()
    {
        if (isset($this->fetchedOrder->parent_id) && $this->fetchedOrder->parent_id != '') {
            $reservation = StoreReservation::query()->where('id', $this->fetchedOrder->parent_id)->first();
            $reservation->update([
                'end_time'    => now()->format('H:i'),
                'is_checkout' => 1,
                'checkout_from' => 'pos',
            ]);
            ReservationOrderItem::query()->where('reservation_id', $reservation->id)->update(['split_payment_status' => 0]);
            ReservationServeRequest::query()->where('reservation_id', $reservation->id)
                ->where('is_served', 0)
                ->update(['is_served' => 1]);
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'remove_booking');
        }
    }

    public function updateRefOrders()
    {
        if (isset($this->fetchedOrder->ref_id) && $this->fetchedOrder->ref_id != '') {
            $oldOrder = Order::query()->where('id', $this->fetchedOrder->ref_id)->first();
            if ($oldOrder) {
                $oldOrder->update(['is_ignored' => 1]);
            }
        }
    }

    public function parentOrderCoupon_ReservationCheckout_OrderIgnore_Socket($parentOrder)
    {
        if (isset($this->payload->is_last_payment) && $this->payload->is_last_payment == 1) {
            $final_discount = SubOrder::query()->where('parent_order_id', $this->fetchedOrder->parent_order_id)->sum('coupon_price');
            if ($parentOrder) {
                $parentOrder->update([
                    'status'       => 'paid',
                    'paid_on'      => Carbon::now()->format('Y-m-d H:i:s'),
                    'coupon_price' => $final_discount,
                    'total_price'  => (float) $parentOrder->total_price - (float) $final_discount,
                ]);
                $parentOrder->refresh();
            }
            if (isset($parentOrder->parent_id) && $parentOrder->parent_id != '') {
                StoreReservation::query()->where('id', $parentOrder->parent_id)->update([
                    'end_time'    => Carbon::now()->format('H:i'),
                    'is_checkout' => 1,
                    'checkout_from' => 'pos',
                ]);
                sendResWebNotification($parentOrder->parent_id, $parentOrder->store_id, 'remove_booking');
            }
            if (isset($parentOrder->ref_id) && $parentOrder->ref_id != '') {
                $oldOrder = Order::query()->where('id', $parentOrder->ref_id)->first();
                if ($oldOrder) {
                    $oldOrder->update(['is_ignored' => 1]);
                }
            }
            if (isset($this->fetchedOrder->reservation_id) && ! is_null($this->fetchedOrder->reservation_id)) {
                StoreReservation::where('id', $this->fetchedOrder->reservation_id)->update([
                    'end_time'    => Carbon::now()->format('H:i'),
                    'is_checkout' => 1,
                    'checkout_from' => 'pos',
                ]);
                sendResWebNotification($this->fetchedOrder->reservation_id, $this->fetchedOrder->store_id, 'remove_booking');
            }
        }
    }
}
