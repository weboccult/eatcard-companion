<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendOrderSms;

/**
 * @author Darshit Hedpara
 */
class MultiSafeDineInOrderWebhook extends BaseWebhook
{
    use DineInWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function handle(): bool
    {
        companionLogger('MultiSafe webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('MultiSafe payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();

        $oldStatus = $this->fetchedOrder->status;

        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedOrder->id.'-'.$this->fetchedOrder->order_id);

        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
        } else {
            $formattedStatus = $payment['status'];
        }

        companionLogger('MultiSafe webhook Payment status', json_encode(['payment_status' => $formattedStatus.'-'.$oldStatus], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_data['multisafe_payment_id'] = $payment['transaction_id'];
        $update_data['status'] = $formattedStatus;
//        Session::put('payment_update', ['status'  => $formattedStatus]);
//        $dine_in_data['payment_update'] = ['status' => $formattedStatus];
        if ($formattedStatus == 'paid' && $oldStatus != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
        }
        $this->updateOrder($update_data);
        if ($formattedStatus == 'paid' && $oldStatus != 'paid') {
            $this->sendNotifications();
            sendOrderSms($this->fetchedStore, $this->fetchedOrder);
            $this->checkoutReservationAndForgetSession();
            $this->sendPrintJsonToSQS();
        }

        return true;
    }
}
