<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Exception;
use Illuminate\Support\Facades\Session;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Models\DineinCart;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MollieDineInOrderSuccessRedirect extends BaseWebhook
{
    /**
     * @throws Exception
     *
     * @return array
     */
    public function handle(): array
    {
        companionLogger('Mollie success redirect request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();
        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedOrder->mollie_payment_id);
        $oldStatus = $this->fetchedOrder->status;
        $message_status = '';
        if ($payment->status == 'paid') {
            $message_status = 'order_received';
        }
        if ($payment->status == 'unpaid') {
            $message_status = 'cancelled';
        }
        if ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired()) {
            Session::put('payment_update', ['status' => $message_status]);
            companionLogger('Mollie payment failed order', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $update_data['mollie_payment_id'] = $payment->id;
        $this->updateOrder($update_data);
        if (isset($this->fetchedOrder) && ! empty($this->fetchedOrder)) {

            //if reservation exist then Clear Cart after Payment Done.
            if (isset($this->fetchedOrder->parent_id) && ! empty($this->fetchedOrder->parent_id) && $payment->status == 'paid') {
                //no need to add table id condition because guest use not have multiple table
                DineinCart::query()->where('reservation_id', $this->fetchedOrder->parent_id)->delete();
            }
            Session::put('payment_update', [
                'id'       => encrypt($this->fetchedOrder['id']),
                'order_id' => $this->fetchedOrder->order_id,
                'status'   => $message_status,
            ]);
            companionLogger('Mollie payment success order #'.$this->fetchedOrder->id.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
            //reset store-qr login after payment done
            if (isset($this->fetchedOrder['dine_in_type']) && ! empty($this->fetchedOrder['dine_in_type']) && $payment->status == 'paid') {
                Session::forget('dine-store-qr-user-login-'.$this->fetchedStore->id);
                Session::forget('dine-store-qr-user-dineintype-'.$this->fetchedStore->id);
            }
        }
        $dine_in_data['status'] = $payment->status;
        $dine_in_data['store'] = $this->fetchedStore->store_slug;

        return $dine_in_data;
    }
}
