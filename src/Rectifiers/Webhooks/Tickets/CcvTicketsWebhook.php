<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class CcvTicketsWebhook extends BaseWebhook
{
    use TicketsWebhookCommonActions;

    /**
     * @throws Exception|GuzzleException
     *
     * @return array|bool|void
     */
    public function handle()
    {
        companionLogger('CCV Tickets webhook request started', 'ReservationId #'.$this->reservationId, 'PaymentId #'.$this->paymentId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('CCV Tickets payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        // this will fetch order from db and set into class property
        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        $this->fetchAndSetPaymentDetails();

        $device = $this->fetchedReservation->kiosk;

        $localPaymentStatus = 'pending';
        $status = $this->fetchedReservation->status;
        $paidOn = null;
        $processType = $this->fetchedPaymentDetails->process_type ?? '';
        $reservationUpdatePayload = $this->fetchedPaymentDetails->payload ?? '';

        $client = new Client();
        $url = $device->environment == 'test' ? config('eatcardCompanion.payment.gateway.ccv.staging') : config('eatcardCompanion.payment.gateway.ccv.production');
        $createOrderUrl = config('eatcardCompanion.payment.gateway.ccv.endpoints.fetchOrder');

        $kiosk_api_key = $device->environment == 'test' ? $device->test_api_key : $device->api_key;
        $api_key = base64_encode($kiosk_api_key.':');

        $request = $client->request('GET', $url.$createOrderUrl.$this->fetchedReservation->id.'-'.$this->fetchedPaymentDetails->id, [
            'headers' => [
                'Authorization' => 'Basic '.$api_key,
                'Content-Type'  => 'application/json;charset=UTF-8',
            ],
        ]);
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        companionLogger('CCV api res', $response);

        $old_status = $this->fetchedReservation->payment_status;

        companionLogger('CCV status response', json_encode(['payment_status' => $response['status']], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data = [];
        $update_payment_data = [];

        $paymentStatus = ($response['status'] == 'success') ? 'paid' : $response['status'];
        if ($response['status'] == 'success' && $old_status != 'paid') {
            $localPaymentStatus = 'paid';
            $paidOn = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = isset($response['details']) ? $response['details']['customerReceipt'] : '';
        } elseif ($response['status'] == 'failed' && $old_status != 'failed') {
            $paymentStatus = 'failed';
            $status = 'cancelled';
            $localPaymentStatus = 'failed';
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

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
