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
        $this->fetchAndSePaymentDetails();

        if (! empty($this->fetchedReservation) && ! empty($this->fetchedPaymentDetails->paid_on) && $this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            return;
        }
        $update_data['payment_status'] = $update_payment_data['payment_status'] = 'pending';
        $update_data['local_payment_status'] = $update_payment_data['payment_status'] = 'pending';
        if ($this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            $update_data['payment_status'] = $update_payment_data['payment_status'] = 'paid';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'paid';
            $update_data['paid_on'] = $update_payment_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = isset($response['ticket']) ? $response['ticket'] : '';
            companionLogger('Worldline status => final + approved : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final' && $this->payload['cancelled'] == 1) {
            $update_data['payment_status'] = $update_payment_data['payment_status'] = 'canceled';
            $update_data['status'] = $update_payment_data['status'] = 'cancelled';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'failed';
            companionLogger('Worldline status => canceled : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final') {
            companionLogger('Worldline status => final : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'error') {
            $update_data['status'] = $update_payment_data['status'] = 'failed';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'failed';
            companionLogger('Worldline status => error : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'busy') {
            companionLogger('Worldline status => busy : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'informal') {
            companionLogger('Worldline status => informal : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'cardrecognition') {
            companionLogger('Worldline status => cardrecognition : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } else {
            companionLogger('Worldline status => unknown : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        }

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
