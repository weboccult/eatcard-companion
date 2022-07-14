<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Kiosk;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class CcvKioskOrderWebhook extends BaseWebhook
{
    use KioskWebhookCommonActions;

    /**
     * @throws Exception|GuzzleException
     *
     * @return array|bool|void
     */
    public function handle()
    {
        companionLogger('CCV webhook request started', 'OrderId #'.$this->orderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('CCV payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        // this will fetch order from db and set into class property
        $this->fetchAndSetStore();

        $this->fetchAndSetOrder();
        $device = $this->fetchedOrder->kiosk;

        $client = new Client();
        $url = $device->environment == 'test' ? config('eatcardCompanion.payment.gateway.ccv.staging') : config('eatcardCompanion.payment.gateway.ccv.production');
        $createOrderUrl = config('eatcardCompanion.payment.gateway.ccv.endpoints.fetchOrder');

        $kiosk_api_key = $device->environment == 'test' ? $device->test_api_key : $device->api_key;
        $api_key = base64_encode($kiosk_api_key.':');

        $request = $client->request('GET', $url.$createOrderUrl.$this->fetchedOrder->ccv_payment_ref, [
            'headers' => [
                'Authorization' => 'Basic '.$api_key,
                'Content-Type'  => 'application/json;charset=UTF-8',
            ],
        ]);
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        companionLogger('CCV api res', $response);

        $oldStatus = $this->fetchedOrder->status;

        companionLogger('CCV status response', json_encode(['payment_status' => $response['status']], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data['status'] = ($response['status'] == 'success') ? 'paid' : $response['status'];
        if ($response['status'] == 'success' && $this->fetchedOrder->status != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_data['ccv_customer_receipt'] = isset($response['details']) ? $response['details']['customerReceipt'] : '';
        }
        $this->updateOrder($update_data);
        Cache::forget('get-order-'.$this->fetchedOrder->id);
        Cache::tags([ORDERS])->flush();
        if ($response['status'] == 'success' || $response['status'] == 'failed') {
            if ($response['status'] == 'success' && $oldStatus != 'paid') {
                $notificationResponse = $this->sendNotifications();
                if (isset($notificationResponse['exception'])) {
                    return $notificationResponse;
                }
                $this->sendPrintJsonToSQS();
            }
            $this->sendKioskOrderMailToOwner($response['status']);
        }

        return true;
    }
}
