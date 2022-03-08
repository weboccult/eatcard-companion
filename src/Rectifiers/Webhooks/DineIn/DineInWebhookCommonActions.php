<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis as LRedis;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\sendAppNotificationHelper;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;

/**
 * @mixin BaseWebhook
 *
 * @author Darshit Hedpara
 */
trait DineInWebhookCommonActions
{
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
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }
    }

    public function checkoutReservationAndForgetSession()
    {
        if ($this->fetchedReservation && $this->fetchedReservation->is_dine_in == 1) {
            $butler_data = isset($store->StoreButler) && $store->StoreButler ? $store->StoreButler : '';
            if ($butler_data && $butler_data->autocheckout_after_payment == 1) {
                //auto checkout after payment if setting is on
                $this->updateReservation([
                    'end_time'      => Carbon::now()->format('H:i'),
                    'is_checkout'   => 1,
                    'checkout_from' => 'dine_in',
                ]);
                Session::forget('dine-reservation-id-'.$store->id.'-'.$this->fetchedReservation['tables'][0]['table_id']);
                Session::forget('dine-user-name-'.$store->id.'-'.$this->fetchedReservation['tables'][0]['table_id']);
                Session::forget('res_dine_in_id-'.$store->id.'-'.$this->fetchedReservation['tables'][0]['table_id']);
                sendResWebNotification($this->fetchedReservation->id, $store->id, 'remove_booking');
            }
        }
    }

    public function sendPrintJsonToSQS()
    {
        // Todo : get print JSON data from EatcardPrint Service
        $printRes = [];
        if ($printRes && ! empty($printRes)) {
            config([
                'queue.connections.sqs.region' => $this->store->sqs->sqs_region,
                'queue.connections.sqs.queue'  => $this->store->sqs->sqs_queue_name,
                'queue.connections.sqs.prefix' => $this->store->sqs->sqs_url,
            ]);
            Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->store->sqs->sqs_queue_name);
        }
    }
}
