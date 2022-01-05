<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\StoreReservation;

/**
 * @description Stag 9
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
                $this->afterEffects = ['undo_operation_requested' => true];
            }
        }
    }

    protected function couponOperation()
    {
    }

    protected function deductCouponAmountFromPurchaseOrderOperation()
    {
    }
}
