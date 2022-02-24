<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Session;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;

/**
 * @description Stag 14
 *
 * @author Darshit Hedpara
 */
trait Stage14Notification
{
    protected function sendEmailLogic()
    {
    }

    protected function setSessionPaymentUpdate()
    {
        Session::put('payment_update', [
            'status'   => 'paid',
            'order_id' => $this->createdOrder->id,
            // 'payment_status' => $order['status'],
            'message'  => __companionTrans('takeaway.order_success_msg', [
                'time'       =>  $this->createdOrder->order_time,
                'order_type' => __companionTrans('general.'.$this->createdOrder->order_type),
            ]),
        ]);
        Session::save();
    }
}
