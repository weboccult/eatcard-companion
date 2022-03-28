<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard;

use Carbon\Carbon;
use Exception;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MollieGiftCardWebhook extends BaseWebhook
{
    use GiftCardWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Mollie webhook request started', 'OrderId #'.$this->giftCardPurchaseOrderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Mollie payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetStore();
        $this->fetchAndSetGiftCardPurchaseOrder();

        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedGiftPurchaseOrder->mollie_payment_id);
        $oldStatus = $this->fetchedGiftPurchaseOrder->status;

        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_data['mollie_payment_id'] = $payment->id;
        $update_data['status'] = $payment->status;
        $this->updateGiftCardPurchaseOrder($update_data);
        if ($payment->status == 'paid' && $oldStatus != 'paid') {
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
