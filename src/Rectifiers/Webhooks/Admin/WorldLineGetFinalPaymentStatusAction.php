<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Admin;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Admin\WorldLineWebhookCommonActions;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class WorldLineGetFinalPaymentStatusAction extends BaseWebhook
{
    use WorldLineWebhookCommonActions;

    /**
     * @throws Exception|GuzzleException
     *
     * @return Builder|Model|void
     */
    public function handle()
    {
        companionLogger('MultiSafe webhook request started', 'OrderId #'.$this->giftCardPurchaseOrderId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('MultiSafe payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        if ($this->orderType == 'sub_order') {
            $this->fetchedOrder = SubOrder::with('parentOrder.kiosk')->where(['id' => $this->orderId])->firstOrFail();
            $order = $this->fetchedOrder;
            $device = $order->parentOrder->kiosk;
        } else {
            $order = $this->fetchAndSetOrder();
            $device = $order->kiosk;
        }
        $this->storeId = $order->store_id;
        $this->fetchAndSetStore();
        if (! empty($this->fetchedOrder) && ! empty($this->fetchedOrder->paid_on) && $this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            return;
        }
        $inputs = [
            'terminal'  => $device['terminal_id'],
            'reference' => $order->id,
            'amount'    => $order->total_price,
            'txid'      => $this->orderType.'-'.$order->id.'-'.$order->store_id,
            'ssai'      => $order->worldline_ssai,
        ];
        $client = new Client();
        $url = $device['environment'] == 'live' ? 'https://wipay.worldline.nl' : 'https://wipayacc.worldline.nl';
        $request_data = $client->request('POST', $url.'/api/2.0/json/status', [
            'headers' => ['Content-Type' => 'application/json;charset=UTF-8'],
            'cert'    => public_path('worldline/eatcard.nl.pem'),
            'ssl_key' => public_path('worldline/eatcard.nl.key'),
            'body'    => json_encode($inputs, true),
        ]);
        $request_data->getHeaderLine('content-type');
        $response = json_decode($request_data->getBody()->getContents(), true);
        $update_data['status'] = 'pending';
        if ($response['status'] == 'final' && $response['approved'] == 1) {
            $update_data['status'] = 'paid';
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_data['worldline_customer_receipt'] = isset($response['ticket']) ? $response['ticket'] : '';
            companionLogger('Worldline status => final + approved : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'final' && $response['cancelled'] == 1) {
            $update_data['status'] = 'canceled';
            companionLogger('Worldline status => canceled : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'final') {
            companionLogger('Worldline status => final : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'error') {
            $update_data['status'] = 'failed';
            companionLogger('Worldline status => error : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'busy') {
            companionLogger('Worldline status => busy : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'informal') {
            companionLogger('Worldline status => informal : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($response['status'] == 'cardrecognition') {
            companionLogger('Worldline status => cardrecognition : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } else {
            companionLogger('Worldline status => unknown : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        }
        if ($this->payload['iteration'] == 1) {
            $update_data['is_uncertain_status'] = 1;
        }
        $this->updateOrder($update_data);

        if ($this->orderType == 'order') {
            $this->sendNotifications();
            $this->sendEmails();
            $this->sendPrintJsonToSQS();
        }

        return $this->fetchedOrder;
    }
}
