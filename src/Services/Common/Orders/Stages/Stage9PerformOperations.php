<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\AfterEffectOrderTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

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
        //skip for dine-in
        if ($this->system == SystemTypes::DINE_IN) {
            return;
        }

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
        $qrCode = '';
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            $qrCode = isset($this->payload['qr_code']) && $this->payload['qr_code'] ? $this->payload['qr_code'] : null;
        }
        if ($this->system === SystemTypes::TAKEAWAY) {
            $qrCode = isset($this->payload['gift_card_code']) && $this->payload['gift_card_code'] ? $this->payload['gift_card_code'] : null;
        }
        if (! empty($qrCode)) {
            $this->coupon = GiftPurchaseOrder::query()
                ->where('qr_code', $qrCode)
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

                    $this->orderData['status'] = $this->orderData['is_paylater_order'] == 1 ? 'pending' : 'paid';
                    $this->orderData['method'] = $this->orderData['is_paylater_order'] == 1 ? null : 'cash';
                    $this->orderData['paid_on'] = $this->orderData['is_paylater_order'] == 1 ? null : Carbon::now()->format('Y-m-d H:i:s');
                } else {
                    $this->couponRemainingPrice = 0;
                    $this->orderData['coupon_price'] = 0;
                }
            }
        }
    }

    protected function asapOrderOperation()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            if (! isset($this->payload['is_asap'])) {
                $store_time_slots = json_decode($this->takeawaySetting->pickup_delivery_days, true);
                $current_day = Carbon::parse($this->orderData['order_date'])->dayOfWeekIso - 1;
                if (isset($store_time_slots[$current_day]['is_max_order_per_slot']) &&
                    $store_time_slots[$current_day]['is_max_order_per_slot'] == '1' &&
                    $store_time_slots[$current_day]['max_order_per_slot'] != null &&
                    (int) $store_time_slots[$current_day]['max_order_per_slot'] > 0) {
                    companionLogger('working sleep function');
                    if (Session::has('order_create_'.$this->orderData['store_id'])) {
                        sleep(5);
                    }
                    Session::push('order_create_'.$this->orderData['store_id'], 'Create takeaway order');
                    $max_number_of_order = $store_time_slots[$current_day]['max_order_per_slot'];
                    $same_time_slot_order = Order::where('order_time', $this->orderData['order_time'])
                        ->where('store_id', $this->orderData['store_id'])
                        ->where('order_date', $this->orderData['order_date'])
                        ->where('created_from', 'takeaway')
                        ->whereIn('status', ['pending', 'paid', 'initialized'])
                        ->count();
                    if ($max_number_of_order <= $same_time_slot_order) {
                        $this->setDumpDieValue(['maximum_order_limit' => 'Maximum order limit reach. Please select another time slot.']);
                    }
                }
            }
        }
    }
}
