<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation;

use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class MollieReservationSuccessRedirect extends BaseWebhook
{
    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('Mollie success request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetReservation();
        $this->fetchAndSetStore();

        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedReservation->mollie_payment_id);

        if ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired()) {
            Session::put('booking_payment_update', ['status' => $payment->status, 'message' => __('messages.booking_failed_msg')]);
            companionLogger('Mollie booking payment failed order', 'OrderId #'.$this->fetchedReservation->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }

        $updateData['mollie_payment_id'] = $payment->id;
        $this->updateReservation($updateData);

        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        if ($payment->status == 'paid') {
            $data['email'] = $this->fetchedReservation->email;
            $data['date'] = $this->fetchedReservation->res_date;
            $data['slot'] = $this->fetchedReservation->from_time;
            $data['end_time'] = $this->fetchedReservation->end_time;
            $data['payment_type'] = $this->fetchedReservation->payment_type;
            if ($this->fetchedReservation->status == 'cancelled' || $this->fetchedReservation->status == 'declined') {
                Session::put('booking_payment_update', ['status' => 'Geannuleerd', 'message' => __('messages.booking_failed_msg')]);
            } else {
                Session::put('booking_payment_update', ['status' => $payment->status, 'extra' => $data]);
            }
            companionLogger('Mollie booking payment success order', 'ReservationId #'.$this->fetchedReservation->id, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

            $this->fetchedReservation->payment_status = $payment->status;
            $this->fetchedReservation->local_payment_status = $payment->status;
        } else {
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'remove_booking');
        }

        return $this->domainUrl.'?status='.$payment->status.'&store='.$this->fetchedStore->store_slug.'&type=reservation';
    }
}
