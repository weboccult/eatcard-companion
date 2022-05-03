<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeGiftCardWebhook extends BaseWebhook
{
    use GiftCardWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('MultiSafe webhook request started', 'OrderId #'.$this->giftCardPurchaseOrderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('MultiSafe payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        // this will fetch order from db and set into class property
        $this->fetchAndSetStore();
        $this->fetchAndSetGiftCardPurchaseOrder();

        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder('GC - '.$this->fetchedGiftPurchaseOrder->id.'-'.$this->fetchedGiftPurchaseOrder->order_id);

        $oldStatus = $this->fetchedGiftPurchaseOrder->status;

        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
        } else {
            $formattedStatus = $payment['status'];
        }

        companionLogger('MultiSafe status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_data['multisafe_payment_id'] = $payment['transaction_id'];
        $update_data['status'] = $formattedStatus;
        $this->updateGiftCardPurchaseOrder($update_data);
        if ($formattedStatus == 'paid' && $oldStatus != 'paid') {
            $update_data = [];
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $this->updateGiftCardPurchaseOrder($update_data);
            $this->sendAppNotification();

            $is_notification = 1;
            $tempData = ['orderDate' => $this->fetchedGiftPurchaseOrder->date, 'is_notification' => $is_notification];
            $this->sendWebNotification($this->fetchedStore, $this->fetchedGiftPurchaseOrder, $tempData);

            $qrImage = $this->generateQRCode();
            $this->sendUserEmails($qrImage);
            $this->sendOwnerEmail($qrImage);
        }

        return true;
    }
}
