<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class MollieGiftCardSuccessRedirect extends BaseWebhook
{
    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('Mollie success request started', 'OrderId #'.$this->giftCardPurchaseOrderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetGiftCardPurchaseOrder();
        $this->fetchAndSetStore();

        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedGiftPurchaseOrder->mollie_payment_id);

        if ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired()) {
            Session::put('payment_update', ['status' => $payment->status, 'message' => __companionTrans('giftcard.gift_purchase_failed_msg')]);
            companionLogger('Mollie gitcard payment failed order', 'OrderId #'.$this->fetchedGiftPurchaseOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }

        $updateData['mollie_payment_id'] = $payment->id;
        $this->updateGiftCardPurchaseOrder($updateData);
        if ($payment->status == 'paid') {
            Session::put('payment_update', ['status' => $payment->status, 'message' => __companionTrans('giftcard.gift_purchase_success_msg')]);
            companionLogger('Mollie payment success order #' . $this->giftCardPurchaseOrderId. ', IP address : '.request()->ip(). ', browser : '. request()->header('User-Agent'));
        }
        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));


        return $this->domainUrl.'?status='.$payment->status.'&store='.$this->fetchedStore->store_slug.'&order-type=gift-card';
    }
}
