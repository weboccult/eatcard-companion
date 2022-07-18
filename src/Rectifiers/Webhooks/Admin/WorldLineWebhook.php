<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Admin;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Redis as LRedis;
use Weboccult\EatcardCompanion\Models\Order;
use GuzzleHttp\Client;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\sendAppNotificationHelper;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;

/**
 * @author Darshit Hedpara
 */
class WorldLineWebhook extends BaseWebhook
{
    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Worldline webhook request started', 'OrderId #'.$this->orderId.' | OrderType : '.$this->orderType, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Worldline payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        $array = explode('-', $this->payload['txid']);
        $ssai = $this->payload['ssai'];
        $order_id = $this->orderId;
        $store_id = $this->storeId;
        $is_last_payment = isset($array[3]) && (int) $array[3] == 1;
        $this->fetchAndSetStore();
        if ($this->orderType == 'sub_order') {
            $order = $this->fetchAndSetSubOrder($ssai);
        } else {
            $this->orderId = $order_id;
            $order = $this->fetchAndSetOrder();
        }
        if (! empty($this->fetchedOrder) && ! empty($this->fetchedOrder->paid_on) && $this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            return;
        }
        $force_refresh = 0;
        $update_data['status'] = 'pending';
        if ($this->payload['status'] == 'final' && $this->payload['approved'] == 1) {
            $update_data['status'] = 'paid';
            if ($this->orderType == 'order') {
                if ($order['is_paylater_order'] == 1) {
                    $force_refresh = 1;
                    $update_data['kiosk_id'] = null;
                }
                $update_data['is_paylater_order'] = 0;
            }
            $update_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_data['worldline_customer_receipt'] = isset($this->payload['ticket']) ? $this->payload['ticket'] : '';
            companionLogger('Worldline status => final + approved : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final' && $this->payload['cancelled'] == 1) {
            if ($this->orderType == 'order' && $order['is_paylater_order'] == 1) {
                $update_data['kiosk_id'] = null;
            }
            $update_data['status'] = 'canceled';
            companionLogger('Worldline status => canceled : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'final') {
            companionLogger('Worldline status => final : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($this->payload, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        } elseif ($this->payload['status'] == 'error') {
            if ($this->orderType == 'order' && $order['is_paylater_order'] == 1) {
                $update_data['kiosk_id'] = null;
            }
            $update_data['status'] = 'failed';
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
        $device = null;
        if ($this->orderType == 'sub_order') {
            $parent_order = Order::with([
                'orderItems',
                'subOrders.subOrderItems',
            ])->where('id', $this->fetchedOrder->parent_order_id)->first();
            if ($is_last_payment) {
                /*We need to handle this condition*/
                $total_coupon_price = SubOrder::query()
                    ->where('parent_order_id', $this->fetchedOrder->parent_order_id)
                    ->sum('coupon_price');
                if ($parent_order) {
                    $parent_order->update([
                        'status'       => 'paid',
                        'paid_on'      => Carbon::now()->format('Y-m-d H:i:s'),
                        'coupon_price' => $total_coupon_price,
                        'total_price'  => (float) $parent_order->total_price - (float) $total_coupon_price,
                    ]);
                    // payment fields not needed here as it will be only updatd while some amount is paid from
                    // reservation iframe
                    if (isset($parent_order['parent_id']) && $parent_order['parent_id'] != '') {
                        StoreReservation::query()->where('id', $parent_order['parent_id'])->update([
                            'end_time'    => date('Y-m-d H:i:s'),
                            'is_checkout' => 1,
                        ]);
                    }
                }
            }
        } else {
            $device = $this->fetchedOrder->kiosk;
        }
        $this->updateOrder($update_data);
        if ($this->orderType == 'order') {
            if (! empty($this->fetchedOrder)) {
                if ($this->payload['status'] == 'final' && $this->payload['status'] == 'paid') {
                    if (isset($this->fetchedOrder->parent_id) && $this->fetchedOrder->parent_id != '') {
                        StoreReservation::query()->where('id', $this->fetchedOrder->parent_id)->update([
                            'end_time'    => date('Y-m-d H:i:s'),
                            'is_checkout' => 1,
                        ]);
                        sendResWebNotification($this->fetchedOrder->parent_id, $this->fetchedStore->id, 'remove_booking', [], $force_refresh);
                    }
                    $is_notification = 0;
                    if (($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_dine_in_notification))) {
                        $is_notification = 1;
                        sendAppNotificationHelper($this->fetchedOrder->toArray(), $this->fetchedStore);
                    }
                    $data = [
                        'orderDate'       => $this->fetchedOrder->order_date,
                        'is_notification' => $is_notification,
                    ];
                    $socket_data = sendWebNotification($this->fetchedStore, $this->fetchedOrder->toArray(), $data, $is_last_payment, $force_refresh);
                    if ($socket_data) {
                        $redis = LRedis::connection();
                        if ($this->fetchedOrder->order_type == 'kiosk') {
                            $socket_data['type'] = 'kiosk';
                            $socket_data['kiosk_id'] = $device->id;
                            try {
                                $client = new Client();
                                $domain = config('eatcardCompanion.system_endpoints.kiosk');
                                companionLogger('Kiosk url for sqs print : '. $domain);
                                $client->request('GET', $domain.'/print-admin/'.$store_id.'/'.$this->fetchedOrder->id.'?print=1');
                                companionLogger('Kiosk auto order print from admin success'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
                            } catch (\Exception $e) {
                                companionLogger('kiosk print from admin - auto print queue send error'.$e->getMessage().', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
                            }
                        }
                        $redis->publish('new_order', json_encode($socket_data));
                    }
                }
                if ($this->fetchedOrder->order_type == 'kiosk') {
                    $status = $update_data['status'] == 'paid' ? 'success' : ($update_data['status'] == 'failed' ? 'failed' : '');
                    $this->sendKioskOrderMailToOwner($status);
                }
            }
        }

        if ($this->orderType == 'sub_order' && $update_data['status'] == 'paid') {
            if (isset($parent_order->order_date)) {
                $return_date = $parent_order->order_date;
                $current_data = [
                    'orderDate'       => $return_date,
                    'is_notification' => 1,
                ];
                try {
                    $socket_data = $this->sendWebNotification($this->fetchedStore, $this->fetchedOrder->toArray(), $current_data, $is_last_payment);
                    if ($socket_data) {
                        $redis = LRedis::connection();
                        $redis->publish('new_order', json_encode($socket_data));
                        companionLogger('worldline callback sub order socket : sub order id  : '.$order->id.'  IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
                    }
                } catch (\Exception $e) {
                    companionLogger('Error :- worldline callback sub order socket error '.$e->getMessage().'  : sub order id  : '.$order->id.'  IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
                }
            }
        }

        return true;
    }
}
