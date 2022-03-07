<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway;

use Carbon\Carbon;
use Exception;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Actions\TakeawayWebhookCommonActions;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\createDeliveryDetail;
use function Weboccult\EatcardCompanion\Helpers\sendOrderSms;

/**
 * @author Darshit Hedpara
 */
class MollieTakeawayOrderWebhook extends BaseWebhook
{
    use TakeawayWebhookCommonActions;

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
        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedOrder->mollie_payment_id);
        $oldStatus = $this->fetchedOrder->status;
        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        if ($payment->amountRefunded && $payment->amountRefunded->value > 0) {
            companionLogger('Mollie webhook refund response', json_encode(['amountRefunded' => $payment->amountRefunded->value], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            $this->updateOrder([
                'is_refunded' => 1,
                'status'      => 'refunded',
            ]);

            return true;
        }
        $update_data = [];
        $update_data['mollie_payment_id'] = $payment->id;
        $update_data['status'] = $payment->status;
        if ($payment->status == 'paid' && $this->fetchedOrder->status != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            if ($this->fetchedOrder->order_type == 'delivery') {
                createDeliveryDetail($this->fetchedOrder->id);
            }
        }
        $this->updateOrder($update_data);
        if ($payment->status == 'paid' && $oldStatus != 'paid') {
            $this->applyCouponLogic();
            $notificationResponse = $this->sendNotifications();
            if (isset($notificationResponse['exception'])) {
                return $notificationResponse;
            }
        }
        if ($payment->status != $oldStatus) {
            sendOrderSms($this->fetchedStore, $this->fetchedOrder);
            $this->sendTakeawayUserEmail();
            $this->sendTakeawayOwnerEmail();
        }

        return true;
    }
}
