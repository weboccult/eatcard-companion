<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation;

use Illuminate\Support\Facades\Session;
use Mollie\Api\Exceptions\ApiException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class MultiSafeReservationCancelRedirect extends BaseWebhook
{
    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('Mollie cancel request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetReservation();
        $this->fetchAndSetStore();

        $payment = MultiSafe::setApiKey($this->fetchedStore->multiSafe->api_key)->getOrder($this->fetchedReservation->id.'-'.$this->fetchedReservation->reservation_id);

        $status = 'canceled';
        $updateData['payment_status'] = $status;
        $updateData['local_payment_status'] = 'failed';
        $updateData['multisafe_payment_id'] = $payment['transaction_id'];
        $updateData['is_manually_cancelled'] = 2;
        $updateData['status'] = 'cancelled';
        $this->updateReservation($updateData);
        sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'remove_booking');
        Session::put('booking_payment_update', ['status' => $status, 'message' => __companionTrans('reservation.booking_failed_msg')]);
        companionLogger('Multisafe payment cancel reservation #'.$this->fetchedReservation->id);

        return $this->domainUrl.'?status='.$status.'&store='.$this->fetchedStore->store_slug.'&type=reservation';
    }
}
