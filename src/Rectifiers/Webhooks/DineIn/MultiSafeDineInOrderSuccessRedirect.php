<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Exception;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Models\DineinCart;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeDineInOrderSuccessRedirect extends BaseWebhook
{
    /**
     * @throws Exception
     *
     * @return array
     */
    public function handle(): array
    {
        companionLogger('MultiSafe success redirect request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();
        $payment = MultiSafe::getOrder($this->fetchedOrder->id.'-'.$this->fetchedOrder->order_id);
        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
            $message_status = 'order_received';
        } else {
            $message_status = $payment['status'];
            if ($payment['status'] == 'cancelled' || $payment['status'] == 'void') {
                $formattedStatus = 'canceled';
            } elseif ($payment['status'] == 'declined' || $payment['status'] == 'expired') {
                $formattedStatus = 'failed';
            } else {
                $formattedStatus = $payment['status'];
            }
            if ($formattedStatus == 'paid') {
                $message_status = 'order_received';
            }
            if ($formattedStatus == 'unpaid') {
                $message_status = 'cancelled';
            }
            /*check that MultiSafe payment is failed, canceled or expired or not*/
//            Session::put('payment_update', ['status' => $message_status]);
            $dine_in_data['payment_update'] = ['status' => $message_status];
            companionLogger('MultiSafe payment failed order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $update_data['multisafe_payment_id'] = $payment['transaction_id'];
        $this->updateOrder($update_data);
        if (! empty($this->fetchedOrder) && $formattedStatus == 'paid') {
            if (isset($this->fetchedOrder->parent_id) && ! empty($this->fetchedOrder->parent_id) && $status == 'paid') {
                //no need to add table id condition because guest use not have multiple table
                DineinCart::query()->where('reservation_id', $this->fetchedOrder->parent_id)->delete();
            }
//            Session::put('payment_update', [
//                'id'       => encrypt($this->fetchedOrder['id']),
//                'order_id' => $this->fetchedOrder->order_id,
//                'status'   => $message_status,
//            ]);
            $dine_in_data['id'] = encrypt($this->fetchedOrder['id']);
            $dine_in_data['order_id'] = $this->fetchedOrder->order_id;
            $dine_in_data['messageStatus'] = $message_status;
            companionLogger('MultiSafe payment success order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $dine_in_data['status'] = $formattedStatus;
        $dine_in_data['store'] = $this->fetchedStore->store_slug;

        return $dine_in_data;
    }
}
