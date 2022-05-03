<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Actions\ReservationWebhookCommonActions;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class MultiSafeReservationWebhook extends BaseWebhook
{
    use ReservationWebhookCommonActions;

    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('MultiSafe webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('MultiSafe payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetReservation();
        $this->fetchAndSetStore();

        $oldStatus = $this->fetchedReservation->payment_status;

        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedReservation->id.'-'.$this->fetchedReservation->reservation_id);

        if ($payment['status'] == 'completed') {
            $formattedStatus = 'paid';
        } else {
            $formattedStatus = $payment['status'];
        }

        companionLogger('MultiSafe status response', json_encode(['payment_status' => $formattedStatus], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        if ($this->fetchedReservation->payment_status == 'paid' && $formattedStatus == 'paid') {
            return ['status' => 'success'];
        }

        $updateData['multisafe_payment_id'] = $payment['transaction_id'];
        $updateData['payment_status'] = $formattedStatus;

        if ($formattedStatus == 'paid' || $formattedStatus == 'partial_refunded' || $formattedStatus == 'refunded') {
            if ($formattedStatus == 'paid') {
                $updateData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            }
            $updateData['local_payment_status'] = 'paid';
        } elseif ($formattedStatus == 'pending' || $formattedStatus == 'initialized' || $formattedStatus == 'expired') {
            $updateData['local_payment_status'] = 'pending';
        } else {
            $updateData['local_payment_status'] = 'failed';
        }

        $refund_amount = isset($payment['amount_refunded']) && $payment['amount_refunded'] > 0 ? (float) $payment['amount_refunded'] / 100 : 0;
        if ($formattedStatus == 'refunded') {
            $updateData['total_price'] = 0;
            $updateData['refund_price'] = $refund_amount;
            $updateData['is_refunded'] = 1;
            $updateData['refund_price_date'] = Carbon::now()->format('Y-m-d H:i:s');
        } elseif ($formattedStatus == 'partial_refunded') {
            $updateData['total_price'] = $this->fetchedReservation->original_total_price - $refund_amount;
            $updateData['refund_price'] = $refund_amount;
            $updateData['is_refunded'] = 1;
            $updateData['refund_price_date'] = Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($this->fetchedReservation->payment_status == 'canceled' && $this->fetchedReservation->status == 'cancelled' && $this->fetchedReservation->is_manually_cancelled == 1) {
            $updateData['is_manually_cancelled'] = 1;
            $this->updateReservation($updateData);
            Session::put('booking_payment_update', ['status' => 'Geannuleerd', 'message' => __('messages.booking_failed_msg')]);
            companionLogger('(Multisafe) This reservation is already cancelled manually');
        } elseif ($this->fetchedReservation->local_payment_status == 'pending' && $payment['status'] != 'completed') {
            companionLogger('Multisafe webhook Payment status ', json_encode($this->fetchedReservation));
            $updateData['is_manually_cancelled'] = 0;
            $this->updateReservation($updateData);
        } else {
            companionLogger('Multisafe webhook Payment status : ', json_encode($this->fetchedReservation));
            $updateData['is_manually_cancelled'] = 2;
            $this->updateReservation($updateData);
        }

        if ($formattedStatus == 'paid' && $oldStatus != 'paid') {
            $this->setLimitHoursIntoStoreData();
            $this->sendReservationStatusChangeEmail();
            $this->sendNewReservationEmailToOwner();
            $this->sendNotification();

            /*Publish new reservation socket*/
            companionLogger('Reservation data is : res_id : '.$this->fetchedReservation->id.' | store_id : '.$this->fetchedStore->id);
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'payment_status_update');
        } else {
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'remove_booking');
        }

        return ['status' => 'success'];
    }
}
