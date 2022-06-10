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

        $update_data['status'] = ($response['status'] == 'success') ? 'paid' : $response['status'];
        if ($response['status'] == 'success' && $old_status != 'paid') {
            $update_data['paid_on'] = $update_payment_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = isset($response['details']) ? $response['details']['customerReceipt'] : '';
        } elseif ($response['status'] == 'failed' && $old_status != 'failed') {
            $update_data['payment_status'] = $update_payment_data['payment_status'] = 'failed';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'failed';
        }

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
