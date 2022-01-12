<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Redis as LRedis;
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
        /*send socket data for new order event on pos side*/
        $current_data = [
            'orderDate'       => $this->createdOrder->order_date,
            'is_notification' => 1,
        ];
        $force_refresh = 0;
        if ($this->storeReservation->undo_checkout_count > 0) {
            $force_refresh = 1;
        }
        $socket_data = sendWebNotification($this->store, $this->createdOrder, $current_data, 0, $force_refresh);
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }
    }

    protected function sendAppNotification()
    {
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
