<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn;

use Carbon\Carbon;
use Exception;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendOrderSms;

/**
 * @author Darshit Hedpara
 */
class MollieDineInOrderWebhook extends BaseWebhook
{
    use DineInWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Mollie webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Mollie payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedOrder->mollie_payment_id);
        $oldStatus = $this->fetchedOrder->status;
        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_data['mollie_payment_id'] = $payment->id;
        $update_data['status'] = $payment->status;
        if ($payment->status == 'paid' && $this->fetchedOrder->status != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
        }
        $this->updateOrder($update_data);
        if ($payment->status == 'paid' && $oldStatus != 'paid') {
            $this->sendNotifications();
            sendOrderSms($this->fetchedStore, $this->fetchedOrder);
            $this->checkoutReservationAndForgetSession();
            $this->sendPrintJsonToSQS();
        }

        return true;
    }
}
