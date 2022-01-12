<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\AfterEffectOrderTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\StoreReservation;

/**
 * @description Stag 9
 *
 * @author Darshit Hedpara
 */
trait Stage9PerformOperations
{
    protected function editOrderOperation()
    {
        if (isset($this->payload['ref_id']) && $this->payload['ref_id'] != '') {
            $oldOrder = Order::query()->where('id', $this->payload['ref_id'])->first();
            if ($oldOrder) {
                if (substr($oldOrder->order_id, 0, 1) == 'E') {
                    $this->orderData['order_id'] = 'E'.substr($oldOrder->order_id, 1);
                } else {
                    $this->orderData['order_id'] = 'E'.$oldOrder->order_id;
                }
            }
            if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']) && ! empty($this->storeReservation)) {
                $edit_order_count = $this->storeReservation->edit_count ?? 0;
                StoreReservation::query()
                    ->where('id', $this->payload['reservation_id'])
                    ->update(['edit_count' => $edit_order_count + 1]);
                StoreReservation::withTrashed()->where('id', $this->storeReservation->ref_id)->update([
                    'dinein_price_id'  => $this->storeReservation->dinein_price_id,
                    'all_you_eat_data' => $this->storeReservation->all_you_eat_data,
                ]);
                if ($this->payload['method'] == 'cash') {
                    Order::query()->where('ref_id', $this->payload['ref_id'])->update(['is_ignored' => 1]);
                    $oldOrder = Order::query()->where('id', $this->payload['ref_id'])->first();
                    if ($oldOrder) {
                        $oldOrder->update(['is_ignored' => 1]);
                    }
                }
            }
        }
    }

    protected function undoOperation()
    {
        if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']) && ! empty($this->storeReservation)) {
            $last_order = Order::query()
                ->where('parent_id', $this->payload['reservation_id'])
                ->orderBy('id', 'desc')
                ->first();
            $first_order = Order::query()->where('parent_id', $this->payload['reservation_id'])->first();
            if (isset($first_order) && isset($first_order->order_id) && isset($last_order) && isset($last_order->id)) {
                $this->orderData['order_id'] = $first_order->order_id;
                $this->orderData['ref_id'] = $last_order->id;
                $this->orderData['is_base_order'] = 1;

                $this->setEffect(AfterEffectOrderTypes::UNDO_OPERATION_REQUESTED_EFFECTS);
            }
        }
    }

    protected function couponOperation()
    {
        $this->couponRemainingPrice = 0;
        if (isset($this->payload['qr_code']) && $this->payload['qr_code']) {
            $this->coupon = GiftPurchaseOrder::query()
                ->where('qr_code', $this->payload['qr_code'])
                ->where('status', 'paid')
                ->where('remaining_price', '>', 0)
                ->where('expire_at', '>=', Carbon::now()->format('Y-m-d'))
                ->first();
            if ($this->coupon) {
                $this->orderData['gift_purchase_id'] = $this->coupon->id;
                if ($this->coupon->remaining_price >= $this->orderData['total_price']) {
                    $this->couponRemainingPrice = $this->coupon->remaining_price - $this->orderData['total_price'];
                    $this->orderData['coupon_price'] = $this->orderData['total_price'];
                    $this->orderData['total_price'] = 0;
                } elseif ($this->coupon->remaining_price < $this->orderData['total_price']) {
                    $this->couponRemainingPrice = 0;
                    $this->orderData['coupon_price'] = $this->coupon->remaining_price;
                    $this->orderData['total_price'] = $this->orderData['total_price'] - $this->coupon->remaining_price;
                } else {
                    $this->couponRemainingPrice = 0;
                    $this->orderData['coupon_price'] = 0;
                }
            }
        }
    }
}
