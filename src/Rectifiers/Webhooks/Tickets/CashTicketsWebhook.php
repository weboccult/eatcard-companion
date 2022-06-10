<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class CashTicketsWebhook extends BaseWebhook
{
    use TicketsWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Cash Tickets webhook request started', 'ReservationId #'.$this->reservationId, 'PaymentId #'.$this->paymentId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Cash Tickets payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        $this->fetchAndSetPaymentDetails();

        $paymentStatus = 'pending';
        $localPaymentStatus = 'pending';
        $status = $this->fetchedReservation->status;
        $paidOn = null;
        $processType = $this->fetchedPaymentDetails->process_type ?? '';
        $reservationUpdatePayload = $this->fetchedPaymentDetails->payload ?? '';

        if ($this->payload['status'] == 'paid') {
            $paymentStatus = 'paid';
            $localPaymentStatus = 'paid';
            $paidOn = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = 'fake-bop payment';
        } elseif ($this->payload['status'] == 'failed') {
            $paymentStatus = 'failed';
            $localPaymentStatus = 'failed';
        } else {
            companionLogger('invalid cash payment status', $this->payload);
        }

        if ($processType == 'update' && ! empty($reservationUpdatePayload) && $paymentStatus == 'paid') {
            $reservationUpdatePayload = json_decode($reservationUpdatePayload, true);
            $reservationUpdatePayload['all_you_eat_data'] = json_encode($reservationUpdatePayload['all_you_eat_data']);
            $update_data = $reservationUpdatePayload;
        }

        if ($processType == 'create') {
            $update_data['status'] = $status;
            $update_data['payment_status'] = $paymentStatus;
            $update_data['local_payment_status'] = $localPaymentStatus;
            $update_data['paid_on'] = $paidOn;
        }

        $update_payment_data['payment_status'] = $paymentStatus;
        $update_payment_data['local_payment_status'] = $localPaymentStatus;
        $update_payment_data['paid_on'] = $paidOn;

        companionLogger('------update cash data', $update_data, $update_payment_data);

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
