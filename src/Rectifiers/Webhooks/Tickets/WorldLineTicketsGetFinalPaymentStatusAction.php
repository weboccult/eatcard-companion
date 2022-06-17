<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class WorldLineTicketsGetFinalPaymentStatusAction extends BaseWebhook
{
    use TicketsWebhookCommonActions;

    public function handle()
    {
        companionLogger('Worldline Tickets webhook manual request started', 'ReservationId #'.$this->reservationId, 'PaymentId #'.$this->paymentId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        $this->fetchAndSetPaymentDetails();

        if (! empty($this->fetchedReservation) && ! empty($this->fetchedPaymentDetails->paid_on)) {
            return $this->fetchedReservation;
        }

        $device = $this->fetchedReservation->kiosk ?? [];

        if (empty($device)) {
            return ['error' => 'Kiosk device not found'];
        }

        $inputs = [
            'terminal'  => $device['terminal_id'],
            'reference' => $this->fetchedReservation->id,
            'amount'    => $this->fetchedReservation->total_price,
            'txid'      => 'reservation'.'-'.$this->fetchedReservation->id.'-'.$this->fetchedReservation->store_id.'-'.($this->fetchedPaymentDetails->id ?? 0),
            'ssai'      => $this->fetchedPaymentDetails->transaction_id,
        ];

        $client = new Client();
        $url = $device->environment == 'live' ? config('eatcardCompanion.payment.gateway.wipay.production') : config('eatcardCompanion.payment.gateway.wipay.staging');

        $createOrderUrl = config('eatcardCompanion.payment.gateway.wipay.endpoints.fetchOrderStatus');

        $request_data = $client->request('POST', $url.$createOrderUrl, [
            'headers' => ['Content-Type' => 'application/json;charset=UTF-8'],
            'cert'    => public_path('worldline/eatcard.nl.pem'),
            'ssl_key' => public_path('worldline/eatcard.nl.key'),
            'body'    => json_encode($inputs, true),
        ]);

        $request_data->getHeaderLine('content-type');
        companionLogger('------------------wipay response', $request_data->getBody()->getContents());
        $response = json_decode($request_data->getBody()->getContents(), true);

        $update_data = [];
        $update_payment_data = [];

        $paymentStatus = 'pending';
        $localPaymentStatus = 'pending';
        $status = $this->fetchedReservation->status;
        $paidOn = null;
        $processType = $this->fetchedPaymentDetails->process_type ?? '';
        $reservationUpdatePayload = $this->fetchedPaymentDetails->payload ?? '';
        companionLogger('------update reservation payload', $reservationUpdatePayload);

        if ($response['status'] == 'final' && $response['approved'] == 1) {
            $paymentStatus = 'paid';
            $localPaymentStatus = 'paid';
            $paidOn = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = $this->payload['ticket'] ?? '';
            companionLogger('Worldline status => final + approved : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'final' && $response['cancelled'] == 1) {
            $paymentStatus = 'canceled';
            $status = 'cancelled';
            $localPaymentStatus = 'failed';
            companionLogger('Worldline status => canceled : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'final') {
            companionLogger('Worldline status => final : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($response['status'] == 'error') {
            $paymentStatus = 'failed';
            $status = 'cancelled';
            $localPaymentStatus = 'failed';
            companionLogger('Worldline status => error : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'busy') {
            companionLogger('Worldline status => busy : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($response['status'] == 'informal') {
            companionLogger('Worldline status => informal : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } elseif ($response['status'] == 'cardrecognition') {
            companionLogger('Worldline status => cardrecognition : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

            return true;
        } else {
            companionLogger('Worldline status => unknown : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));

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

            if ($this->payload['iteration'] == 1) {
                $update_data['is_uncertain_status'] = 1;
            }
        }

        $update_payment_data['payment_status'] = $paymentStatus;
        $update_payment_data['local_payment_status'] = $localPaymentStatus;
        $update_payment_data['paid_on'] = $paidOn;

        if ($update_payment_data['local_payment_status'] == 'failed') {
            $update_payment_data['cancel_from'] = 'webhook';
        }

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return $this->fetchedReservation;
    }
}
