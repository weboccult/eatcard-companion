<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis as LRedis;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use function Weboccult\EatcardCompanion\Helpers\sendAppNotificationHelper;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;

/**
 * @description Stag 13
 *
 * @author Darshit Hedpara
 */
trait Stage13Broadcasting
{
    protected function sendWebNotification()
    {
        //send only for cash,pin,pay-later orders and Kiosk bop payments
        if (! (in_array($this->orderData['method'], ['cash', 'pin']) || $this->createdOrder->is_paylater_order == 1 || $this->settings['bop_kiosk']['status'])) {
            return;
        }

        /*send socket data for new order event on pos side*/
        $is_notification = $this->settings['notification']['status'] ? 1 : 0;
        $current_data = [
            'orderDate'       => $this->createdOrder->order_date,
            'is_notification' => $is_notification,
        ];
        $force_refresh = 0;
        if (! empty($this->storeReservation) && $this->storeReservation->undo_checkout_count > 0) {
            $force_refresh = 1;
        }
        $order = $this->createdOrder->toArray();
        $socket_data = sendWebNotification($this->store, $order, $current_data, 0, $force_refresh);

        if ($this->system === SystemTypes::KIOSK) {
            $socket_data['type'] = 'kiosk';
            $socket_data['kiosk_id'] = $order->kiosk->id ?? null;
        }

        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }

        //need to uto checkout guest user after his order place if setting is on.
        if ($this->system === SystemTypes::DINE_IN) {
            if (! empty($this->storeReservation) && $this->storeReservation->is_dine_in == 1) {
                $autocheckout_after_payment = isset($this->store->storeButler->autocheckout_after_payment) && $this->store->storeButler->autocheckout_after_payment ?? 0;
                if ($autocheckout_after_payment == 1) {
                    sendResWebNotification($this->storeReservation->id, $this->store->id, 'remove_booking');
                }
            }
        }
    }

    protected function sendAppNotification()
    {
        $isSendAppNotification = false;
        //no need to send OneSignal notification for pay-later order because current we're not showing palate order in partner app
        if ($this->system === SystemTypes::TAKEAWAY && ($this->orderData['method'] == 'cash' /*|| $this->createdOrder->is_paylater_order == 1*/)) {
            if (($this->store->is_notification) && (! $this->store->notificationSetting || ($this->store->notificationSetting && $this->store->notificationSetting->is_takeaway_notification))) {
                $isSendAppNotification = true;
            }
        }

        if ($this->system === SystemTypes::KIOSK && $this->settings['bop_kiosk']['status']) {
            if (($this->store->is_notification) && (! $this->store->notificationSetting || ($this->store->notificationSetting && $this->store->notificationSetting->is_dine_in_notification))) {
                $isSendAppNotification = true;
            }
        }

        if ($isSendAppNotification) {
            /*send app notification after order status updated to paid or canceled*/
            $order = Order::query()->with('orderItems.product:id,image,sku')->findOrFail($this->createdOrder->id);
            $order = $order->toArray();
            //$order['paid_on'] = Carbon::parse($order['paid_on'])->format('d-m-Y H:i');
            $order['order_date'] = Carbon::parse($order['order_date'])->format('d-m-Y');
            foreach ($order['order_items'] as $key => $item) {
                $order['order_items'][$key]['extra'] = json_decode($item['extra']);
            }
            sendAppNotificationHelper($order, $this->store);
        }
    }

    protected function socketPublish()
    {
        // already published above and it's depended on webNotification
        // so skipping it here...
    }

    protected function newOrderSocketPublish()
    {
    }

    protected function checkoutReservationSocketPublish()
    {
    }
}
