<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Exception;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeDineInOrderCancelRedirect extends BaseWebhook
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
//        Session::put('payment_update', ['status' => $status]);
        companionLogger('MultiSafe payment failed order', 'OrderId #'.$this->fetchedOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        $update_data['multisafe_payment_id'] = isset($payment['transaction_id']) ? $payment['transaction_id'] : '';
        $update_data['status'] = $status;
        $this->updateOrder($update_data);
        $dine_in_data['payment_update'] = ['status' => $status];
        $dine_in_data['status'] = $status;
        $dine_in_data['store'] = $this->fetchedStore->store_slug;
        $dine_in_data['orderId'] = $this->fetchedOrder->id;

        return $dine_in_data;
    }
}
