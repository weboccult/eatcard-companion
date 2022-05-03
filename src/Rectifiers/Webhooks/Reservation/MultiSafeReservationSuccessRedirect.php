<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation;

use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class MultiSafeReservationSuccessRedirect extends BaseWebhook
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

        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedReservation->id.'-'.$this->fetchedReservation->reservation_id);

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
            Session::put('booking_payment_update', ['status' => $status, 'message' => __('messages.booking_failed_msg')]);
            companionLogger('Multisafe payment failed reservation #'.$this->fetchedReservation->id);
        }

        $updateData['multisafe_payment_id'] = $payment['transaction_id'];

        $this->updateReservation($updateData);

        if ($status == 'paid') {
            $data['email'] = $this->fetchedReservation->email;
            $data['date'] = $this->fetchedReservation->res_date;
            $data['slot'] = $this->fetchedReservation->from_time;
            $data['end_time'] = $this->fetchedReservation->end_time;
            $data['payment_type'] = $this->fetchedReservation->payment_type;
            if ($this->fetchedReservation->status == 'cancelled') {
                Session::put('booking_payment_update', ['status' => 'Geannuleerd', 'message' => __('messages.booking_failed_msg')]);
            } else {
                Session::put('booking_payment_update', ['status' => $status, 'extra' => $data]);
            }
            companionLogger('Multisafe payment success booking #'.$this->fetchedReservation->id);
        }

        return $this->domainUrl.'?status='.$status.'&store='.$this->fetchedStore->store_slug.'&type=reservation';
    }
}
