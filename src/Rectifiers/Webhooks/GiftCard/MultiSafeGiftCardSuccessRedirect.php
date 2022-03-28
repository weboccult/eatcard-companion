<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard;

use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeGiftCardSuccessRedirect extends BaseWebhook
{
    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('MultiSafe success request started', 'OrderId #'.$this->giftCardPurchaseOrderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetGiftCardPurchaseOrder();
        $this->fetchAndSetStore();

        $payment = MultiSafe::getOrder('GC - '.$this->fetchedGiftPurchaseOrder->id.'-'.$this->fetchedGiftPurchaseOrder->order_id);

        if ($payment['status'] == 'completed') {
            $status = 'paid';
        } else {
            if ($payment['status'] == 'cancelled' || $payment['status'] == 'void') {
                $status = 'canceled';
            } elseif ($payment['status'] == 'declined' || $payment['status'] == 'expired') {
                $status = 'failed';
            } else {
                $status = $payment['status'];
            }
            /*check that multisafe payment is failed, canceled or expired or not*/
            Session::put('payment_update', ['status' => $status, 'message' => __companionTrans('giftcard.gift_purchase_failed_msg')]);
            companionLogger('MultiSafe gitcard payment failed order', 'OrderId #'.$this->fetchedGiftPurchaseOrder->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }

        $updateData['multisafe_payment_id'] = $payment['transaction_id'];
        $this->updateGiftCardPurchaseOrder($updateData);
        if ($status == 'paid') {
            Session::put('payment_update', ['status' => $status, 'message' => __companionTrans('giftcard.gift_purchase_success_msg')]);
            companionLogger('MultiSafe payment success order #'.$this->giftCardPurchaseOrderId.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        }
        companionLogger('MultiSafe status response', json_encode(['payment_status' => $status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        return $this->domainUrl.'?status='.$status.'&store='.$this->fetchedStore->store_slug.'&order-type=gift-card';
    }
}
