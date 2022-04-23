<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway;

use Exception;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeTakeawayOrderSuccessRedirect extends BaseWebhook
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
        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedOrder->id.'-'.$this->fetchedOrder->order_id);
        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
        } else {
            if ($payment['status'] == 'cancelled' || $payment['status'] == 'void') {
                $formattedStatus = 'canceled';
            } elseif ($payment['status'] == 'declined' || $payment['status'] == 'expired') {
                $formattedStatus = 'failed';
            } else {
                $formattedStatus = $payment['status'];
            }
            /*check that MultiSafe payment is failed, canceled or expired or not*/
            Session::put('payment_update', [
                'status'   => $formattedStatus,
                'order_id' => $this->fetchedOrder->id,
                'message'  => __companionTrans('takeaway.order_failed_msg'),
            ]);
            companionLogger('MultiSafe payment failed order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $update_data['multisafe_payment_id'] = $payment['transaction_id'];
        $this->updateOrder($update_data);
        if (! empty($new_order) && $formattedStatus == 'paid') {
            Session::put('payment_update', [
                'status'   => $formattedStatus,
                'order_id' => $this->fetchedOrder->id,
                'message'  => __companionTrans('takeaway.order_success_msg', [
                    'time'       => $this->fetchedOrder->order_time,
                    'order_type' => __companionTrans('general.'.$this->fetchedOrder->order_type),
                ]),
            ]);
            companionLogger('MultiSafe payment success order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $takeaway_data['status'] = $formattedStatus;
        $takeaway_data['store'] = $this->fetchedStore->store_slug;

        return $takeaway_data;
    }
}
