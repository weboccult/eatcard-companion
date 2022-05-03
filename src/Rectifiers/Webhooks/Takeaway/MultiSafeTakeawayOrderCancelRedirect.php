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
class MultiSafeTakeawayOrderCancelRedirect extends BaseWebhook
{
    /**
     * @throws Exception
     *
     * @return array
     */
    public function handle(): array
    {
        companionLogger('MultiSafe cancel redirect request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();
        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedOrder->id.'-'.$this->fetchedOrder->order_id);
        $status = 'canceled';
        /*MultiSafe payment is canceled*/
        Session::put('payment_update', [
            'status'   => $status,
            'order_id' => $this->fetchedOrder->id,
            'message'  => __companionTrans('takeaway.order_failed_msg'),
        ]);
        companionLogger('MultiSafe payment failed order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        $update_data['multisafe_payment_id'] = isset($payment['transaction_id']) ? $payment['transaction_id'] : '';
        $update_data['status'] = $status;
        $this->updateOrder($update_data);
        $takeaway_data['status'] = $status;
        $takeaway_data['store'] = $this->fetchedStore->store_slug;

        return $takeaway_data;
    }
}
