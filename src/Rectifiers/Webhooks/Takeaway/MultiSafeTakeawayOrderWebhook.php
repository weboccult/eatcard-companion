<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Actions\TakeawayDineInCommonActions;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\createDeliveryDetail;
use function Weboccult\EatcardCompanion\Helpers\sendOrderSms;

/**
 * @author Darshit Hedpara
 */
class MultiSafeTakeawayOrderWebhook extends BaseWebhook
{
    use TakeawayDineInCommonActions;

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function handle(): bool
    {
        companionLogger('Mollie webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Mollie payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        // this will fetch order from db and set into class property
        $this->fetchAndSetOrder();
        $this->fetchAndSetStore();

        $oldStatus = $this->fetchedOrder->status;

        $payment = MultiSafe::getOrder($this->fetchedOrder->id.'-'.$this->fetchedOrder->order_id);

        if ($payment['status'] == 'refunded') {
            if ($this->fetchedOrder->is_refunded == 1) {
                return true;
            }
            companionLogger('MultiSafe webhook refund response', json_encode(['amountRefunded' => $payment['amount_refunded']], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            $this->updateOrder([
                'is_refunded' => 1,
                'status'      => 'refunded',
            ]);

            return true;
        }

        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
        } else {
            $formattedStatus = $payment['status'];
        }

        companionLogger('MultiSafe webhook Payment status', json_encode(['payment_status' => $formattedStatus.'-'.$oldStatus], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_data['multisafe_payment_id'] = $payment->id;
        $update_data['status'] = $formattedStatus;
        if ($formattedStatus == 'paid' && $this->fetchedOrder->status != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            if ($this->fetchedOrder->order_type == 'delivery') {
                createDeliveryDetail($this->fetchedOrder->id);
            }
        }
        $this->updateOrder($update_data);
        if ($formattedStatus == 'paid' && $oldStatus != 'paid') {
            $this->applyCouponLogic();
            $notificationResponse = $this->sendNotifications();
            if (isset($notificationResponse['exception'])) {
                return $notificationResponse;
            }
        }
        if ($formattedStatus != $oldStatus) {
            sendOrderSms($this->fetchedStore, $this->fetchedOrder);
            $this->sendTakeawayUserEmail();
            $this->sendTakeawayOwnerEmail();
        }

        return true;
    }
}
