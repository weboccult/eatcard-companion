<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderDeliveryDetails;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\getDistanceBetweenTwoPoints;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @description Stag 11
 *
 * @author Darshit Hedpara
 */
trait Stage11CreateProcess
{
    protected function createOrder()
    {
        $this->createdOrder = Order::query()->create($this->orderData);
    }

    protected function createOrderItems()
    {
        foreach ($this->orderItemsData as $key => $orderItem) {
            $orderItem['order_id'] = $this->createdOrder->id;
            $this->createdOrderItems[] = OrderItem::query()->insert($orderItem);
        }
    }

    protected function forgetSessions()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            Session::forget('order_create_'.$this->store->id);
        }
    }

    protected function markOrderAsFuturePrint()
    {
    }

    protected function markOtherOrderAsIgnore()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (! empty($this->storeReservation)) {
                Order::query()
                    ->where('parent_id', $this->storeReservation->id)
                    ->whereNotIn('id', [$this->createdOrder->id])
                    ->update(['is_base_order' => 0, 'is_ignored' => 1]);
            }
        }
    }

    protected function createDeliveryIntoDatabase()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            if ($this->createdOrder->order_type == 'delivery') {
                try {
                    $driver_to_store_time = '10 mins';
                    $buffer_time = '2 mins';
                    $order = Order::with(['store'])->where('id', $this->createdOrder->id)->first();
                    $approx_distance = 0;
                    $approx_duration = 0;
                    if ($order && $order['store'] && $order['store']->is_delivery_app_enabled) {
                        $delivery_time = $order->order_date.' '.$order->order_time;
                        $delivery_buffer_time = date('Y-m-d H:i:s', strtotime($delivery_time.' -'.$buffer_time));
                        if ($this->takeawaySetting->delivery_radius_setting == 1 || $this->takeawaySetting->delivery_radius_setting == '1') {
                            $preparing_time = $this->takeawaySetting && $this->takeawaySetting->delivery_prep_time ? $this->takeawaySetting->delivery_prep_time.' mins' : '15 mins';
                        } else {
                            $preparing_time = $this->takeawaySetting && $this->takeawaySetting->zipcode_delivery_prep_time ? $this->takeawaySetting->zipcode_delivery_prep_time.' mins' : '15 mins';
                        }
                        if ($order['store']['store_latitude'] && $order['delivery_latitude']) {
                            $distance_data = getDistanceBetweenTwoPoints($order['store']['store_latitude'], $order['store']['store_longitude'], $order['delivery_latitude'], $order['delivery_longitude']);
                            companionLogger('distance data between store and deliveery location - ', json_encode($distance_data), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
                            if (! empty($distance_data)) {
                                $approx_distance = @$distance_data['rows'][0]['elements'][0]['distance']['text'];
                                $approx_duration = @$distance_data['rows'][0]['elements'][0]['duration']['text'];
                            }
                        }
                        companionLogger('distance data between store and deliveery location approx_distance - ', $approx_distance);
                        if ($approx_distance) {
                            $driver_pickup_time = date('Y-m-d H:i:s', strtotime($delivery_buffer_time.' -'.$approx_duration));
                            $driver_sent_request_time = date('Y-m-d H:i:s', strtotime($driver_pickup_time.' -'.$driver_to_store_time));
                            $order_preparation_time = date('Y-m-d H:i:s', strtotime($driver_pickup_time.' -'.$preparing_time));
                            OrderDeliveryDetails::create([
                                'store_id'                            => $order->store_id,
                                'order_id'                            => $this->createdOrder->id,
                                'approx_distance'                     => $approx_distance,
                                'approx_trip_time'                    => $approx_duration,
                                'approx_preparation_time'             => $preparing_time,
                                'approx_restaurant_pickup_time'       => $driver_pickup_time,
                                'approx_driver_request_time'          => $driver_sent_request_time,
                                'approx_order_start_preparation_time' => $order_preparation_time,
                                'cron_status'                         => 0,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    companionLogger('make delivery detail request takeaway error -', $e->getMessage(), 'Line : '.$e->getLine(), 'File : '.$e->getFile(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
                }
            }
        }
    }

    protected function deductCouponAmountFromPurchaseOrderOperation()
    {
        if ($this->orderData['method'] == 'cash' && in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS, SystemTypes::TAKEAWAY])) {
            if (! empty($this->coupon) && ! empty($this->couponRemainingPrice)) {
                if ($this->coupon->is_multi_usage == 1) {
                    $this->coupon->update(['remaining_price' => $this->couponRemainingPrice]);
                } else {
                    $this->coupon->update(['remaining_price' => 0]);
                }
            }
        }
    }

    protected function checkoutReservation()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (! empty($this->storeReservation) && isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id'])) {
                $reservation = StoreReservation::query()
                    ->where('id', $this->storeReservation->id)
                    ->first();
                if (! empty($reservation)) {
                    $reservation->update([
                        'end_time'      => now()->format('H:i'),
                        'is_checkout'   => 1,
                        'checkout_from' => 'pos',
                    ]);
                    ReservationServeRequest::query()
                        ->where('reservation_id', $reservation->id)
                        ->where('is_served', 0)
                        ->update(['is_served' => 1]);
                    sendResWebNotification($reservation->id, $reservation->store_id, 'remove_booking');
                }
            }
        }
    }
}
