<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Reservation;

use Carbon\Carbon;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Actions\ReservationWebhookCommonActions;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class MollieReservationWebhook extends BaseWebhook
{
    use ReservationWebhookCommonActions;

    /**
     * @throws ApiException
     *
     * @return array|mixed
     */
    public function handle(): array
    {
        companionLogger('Mollie webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Mollie payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetReservation();
        $this->fetchAndSetStore();

        Mollie::api()->setApiKey($this->fetchedStore->mollie_api_key);
        $payment = Mollie::api()->payments()->get($this->fetchedReservation->mollie_payment_id);

        companionLogger('Mollie status response', json_encode(['payment_status' => $payment->status], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        if ($payment->amountRefunded && $payment->amountRefunded->value > 0) {
            companionLogger('Mollie webhook refund response', json_encode(['amountRefunded' => $payment->amountRefunded->value], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            $this->updateOrder([
                'is_refunded' => 1,
                'status'      => 'refunded',
            ]);

            return ['status' => 'success'];
        }

        if ($this->fetchedReservation->payment_status == 'paid' && $payment->status == 'paid') {
            return ['status' => 'success'];
        }

        $updateData['mollie_payment_id'] = $payment->id;
        $updateData['payment_status'] = $payment->status;
        $updateData['local_payment_status'] = $payment->status;
        if ($payment->status == 'paid') {
            $updateData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
        } elseif ($payment->status == 'pending' || $payment->status == 'initialized') {
            $updateData['local_payment_status'] = 'pending';
        } else {
            $updateData['status'] = 'cancelled';
            $updateData['local_payment_status'] = 'failed';
        }
        companionLogger('Reservation status', $this->fetchedReservation->status);
        companionLogger('Reservation payment_status', $this->fetchedReservation->payment_status);
        if ($this->fetchedReservation->payment_status == 'canceled' && $this->fetchedReservation->status == 'cancelled' && $this->fetchedReservation->is_manually_cancelled == 1) {
            $updateData['is_manually_cancelled'] = 1;
            $this->fetchedReservation->update($updateData);
            companionLogger('(Mollie) This reservation is already cancelled manually');
        } elseif ($this->fetchedReservation->local_payment_status == 'pending') {
            $updateData['is_manually_cancelled'] = 0;
            $this->fetchedReservation->update($updateData);
        } else {
            $updateData['is_manually_cancelled'] = 2;
            $this->fetchedReservation->update($updateData);
        }

        if ($payment->status == 'paid' && $this->fetchedReservation->payment_status == 'paid') {
            $this->setLimitHoursIntoStoreData();
            $this->sendReservationStatusChangeEmail();
            $this->sendNewReservationEmailToOwner();
            $this->sendNotification();
            /*Publish new reservation socket*/
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'payment_status_update');
        } else {
            sendResWebNotification($this->fetchedReservation->id, $this->fetchedStore->id, 'remove_booking');
        }

        return true;
    }
}
