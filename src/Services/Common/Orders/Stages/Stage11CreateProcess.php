<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\createDeliveryDetail;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @description Stag 11
 *
 * @author Darshit Hedpara
 */
trait Stage11CreateProcess
{
    protected function updateTipAmountInParentOrderIfApplicable()
    {
        if ($this->system == SystemTypes::POS && $this->isSubOrder && ! empty($this->storeReservation) && isset ($this->parentOrder->tip_amount)) {
            $parent_tip = $this->parentOrder->tip_amount ?? 0;
            $tip = $parent_tip + $this->orderData['tip_amount'];
            $updateParentData['tip_amount'] = $tip;
            if (isset($this->payload['is_last_payment']) && $this->payload['is_last_payment'] == 1) {
                $updateParentData['total_price'] = ($this->parentOrder->total_price + $tip);
                $updateParentData['original_order_total'] = ($this->parentOrder->original_order_total + $tip);
            }
            $this->parentOrder->update($updateParentData);
        }
    }

    protected function isSimulateEnabled()
    {
        if ($this->getSimulate()) {
            $this->setDumpDieValue([
                'order_data'       => $this->orderData,
                'order_items_data' => $this->orderItemsData,
            ]);
        }
    }

    protected function createOrder()
    {
        companionLogger('--before order create data : ', $this->orderData);
        $this->createdOrder = Order::query()->create($this->orderData);
	    if(($this->createdOrder->method == 'manual_pin' || $this->createdOrder->method == 'cash') && $this->createdOrder->status == 'paid') {
			if(!is_null($this->createdOrder->gift_purchase_id) && $this->createdOrder->coupon_price) {
				$gift_purchase_coupon = GiftPurchaseOrder::where('id', $this->createdOrder->gift_purchase_id)->first();
				if(isset($gift_purchase_coupon->is_multi_usage) && $gift_purchase_coupon->is_multi_usage == 1) {
					$gift_purchase_remaining_amount = $gift_purchase_coupon->remaining_price - $this->createdOrder->coupon_price;
					$gift_purchase_coupon->update(['remaining_price' => $gift_purchase_remaining_amount]);
				} else {
					$gift_purchase_coupon->update(['remaining_price' => 0]);
				}
			}
	    }
        $this->createdOrder->refresh();
    }

    protected function createOrderItems()
    {
        companionLogger('--before order items create data : ', $this->orderItemsData);
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
                if (isset($this->for_undo_order) && $this->for_undo_order) {
                    Order::query()
                        ->where('parent_id', $this->storeReservation->id)
                        ->whereNotIn('id', [$this->createdOrder->id])
                        ->update(['is_base_order' => 0, 'is_ignored' => 1]);
                } else {
                    Order::query()
                        ->where('parent_id', $this->storeReservation->id)
                        ->whereNotIn('id', [$this->createdOrder->id])
                        ->update(['is_ignored' => 1]);
                }
            }
        }
    }

    protected function createDeliveryIntoDatabase()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            if (($this->createdOrder->method == 'cash' || $this->orderData['is_paylater_order'] == 1) && $this->createdOrder->order_type == 'delivery') {
                createDeliveryDetail($this->createdOrder->id);
            }
        }
    }

    protected function deductCouponAmountFromPurchaseOrderOperation()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS, SystemTypes::TAKEAWAY]) && ($this->orderData['method'] == 'cash' || $this->orderData['is_paylater_order'] == 1)) {
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
                    companionLogger(
                        'Reservation checkout done after order create',
                        [
                            'reservation_id' => $this->storeReservation->id,
                            'order_id' => $this->createdOrder->id,
                        ]
                    );
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
