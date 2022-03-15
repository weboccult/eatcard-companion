<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Pos;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis as LRedis;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;

/**
 * @author Darshit Hedpara
 */
class CcvPosSubOrderWebhook extends BaseWebhook
{
    use PosWebhookCommonActions;

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

        $this->fetchedOrder = SubOrder::query()->findOrFail($this->orderId);
        $parentOrder = Order::query()->with([
            'orderItems',
            'subOrders.subOrderItems',
        ])->where('id', $this->fetchedOrder->parent_order_id)->first();

        $device = KioskDevice::query()->where('id', $this->fetchedOrder->kiosk_id);

        $client = new Client();
        $url = $device->environment == 'test' ? config('eatcardCompanion.payment.gateway.ccv.staging') : config('eatcardCompanion.payment.gateway.ccv.production');
        $createOrderUrl = config('eatcardCompanion.payment.gateway.ccv.endpoints.fetchOrder');

        $kiosk_api_key = $device->environment == 'test' ? $device->test_api_key : $device->api_key;
        $api_key = base64_encode($kiosk_api_key.':');

        $request = $client->request('POST', $url.$createOrderUrl.$this->fetchedOrder->id, [
            'headers' => [
                'Authorization' => 'Basic '.$api_key,
                'Content-Type'  => 'application/json;charset=UTF-8',
            ],
        ]);
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        companionLogger('CCV api res', $response);

        companionLogger('CCV status response', json_encode(['payment_status' => $response['status']], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $update_data['status'] = ($response['status'] == 'success') ? 'paid' : $response['status'];
        if ($response['status'] == 'success' && $this->fetchedOrder->status != 'paid') {
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_data['ccv_customer_receipt'] = isset($response['details']) ? $response['details']['customerReceipt'] : '';
            $this->parentOrderCoupon_ReservationCheckout_OrderIgnore_Socket($parentOrder);
            $this->applyCouponLogic();
        }
        $this->updateOrder($update_data);

        $lastPayment = isset($this->req->is_last_payment) && $this->req->is_last_payment == 1 ? 1 : 0;
        $current_data = [
            'orderDate'       => $parentOrder->order_date,
            'is_notification' => 1,
        ];
        $socket_data = sendWebNotification($this->fetchedStore, $this->fetchedOrder, $current_data, $lastPayment);
        $socket_data['type'] = 'kiosk';
        $socket_data['kiosk_id'] = $this->fetchedOrder->kiosk_id;
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }

        return [
            'parent_order' => $parentOrder,
            'sub_order'    => $this->fetchedOrder,
        ];
    }
}
