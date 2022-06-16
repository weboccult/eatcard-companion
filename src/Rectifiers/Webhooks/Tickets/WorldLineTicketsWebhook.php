<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class WorldLineTicketsWebhook extends BaseWebhook
{
    use TicketsWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Worldline Tickets webhook request started', 'ReservationId #'.$this->reservationId, 'PaymentId #'.$this->paymentId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Worldline Tickets payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        $this->fetchAndSetPaymentDetails();

        if (! empty($this->fetchedReservation) && ! empty($this->fetchedPaymentDetails->paid_on) && $this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            return;
        }

        $update_data = [];
        $update_payment_data = [];

        $paymentStatus = 'pending';
        $localPaymentStatus = 'pending';
        $status = $this->fetchedReservation->status;
        $paidOn = null;
        $processType = $this->fetchedPaymentDetails->process_type ?? '';
        $reservationUpdatePayload = $this->fetchedPaymentDetails->payload ?? '';
        companionLogger('------update reservation payload', $reservationUpdatePayload);

        if ($this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            $paymentStatus = 'paid';
            $localPaymentStatus = 'paid';
            $paidOn = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = $this->payload['ticket'] ?? '';
            companionLogger('Worldline status => final + approved : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final' && $this->payload['cancelled'] == 1) {
            $paymentStatus = 'canceled';
            $status = 'cancelled';
            $localPaymentStatus = 'failed';
            companionLogger('Worldline status => canceled : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final') {
            companionLogger('Worldline status => final : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($this->payload['status'] == 'error') {
            $paymentStatus = 'failed';
            $status = 'cancelled';
            $localPaymentStatus = 'failed';
            companionLogger('Worldline status => error : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'busy') {
            companionLogger('Worldline status => busy : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($this->payload['status'] == 'informal') {
            companionLogger('Worldline status => informal : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($this->payload['status'] == 'cardrecognition') {
            companionLogger('Worldline status => cardrecognition : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } else {
            companionLogger('Worldline status => unknown : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        }

        if ($processType == 'update' && ! empty($reservationUpdatePayload) && $paymentStatus == 'paid') {
            $reservationUpdatePayload = json_decode($reservationUpdatePayload, true);
            $reservationUpdatePayload['all_you_eat_data'] = json_encode($reservationUpdatePayload['all_you_eat_data']);
            $update_data = $reservationUpdatePayload;
            $update_payment_data['payload'] = '';
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

        if ($update_payment_data['local_payment_status'] == 'failed') {
            $update_payment_data['cancel_from'] = 'webhook';
        }

        companionLogger('------update wipay data', $update_data, $update_payment_data);

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
