<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Session;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MollieTakeawayOrderSuccessRedirect extends BaseWebhook
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
        if ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired()) {
            Session::put('payment_update', [
                'status'   => $payment->status,
                'order_id' => $this->orderId,
                'message'  => __companionTrans('takeaway.order_failed_msg'),
            ]);
            companionLogger('Mollie payment failed order', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $update_data['mollie_payment_id'] = $payment->id;
        $update_data['status'] = $payment->status;
        if ($payment->status == 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
        }
        $this->updateOrder($update_data);
        /*set session if payment status updated to paid*/
        if (! empty($new_order) && $payment->status == 'paid') {
            Session::put('payment_update', [
                'status'   => $payment->status,
                'order_id' => $this->fetchedOrder->id,
                'message'  => __companionTrans('takeaway.order_success_msg', [
                    'time'       => $this->fetchedOrder->order_time,
                    'order_type' => __companionTrans('general.'.$this->fetchedOrder->order_type),
                ]),
            ]);
            companionLogger('Mollie payment success order', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
        $takeaway_data['status'] = $payment->status;
        $takeaway_data['store'] = $this->fetchedStore->store_slug;

        return $takeaway_data;
    }
}
