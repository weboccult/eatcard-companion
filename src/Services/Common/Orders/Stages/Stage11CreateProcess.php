<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
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
            $orderItem['sub_order_id'] = $this->createdOrder->id;
            $this->createdOrderItems[] = OrderItem::query()->insert($orderItem);
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

    protected function deductCouponAmountFromPurchaseOrderOperation()
    {
        if ($this->orderData['method'] == 'cash' && in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
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

    protected function createDeliveryIntoDatabase()
    {
    }
}
