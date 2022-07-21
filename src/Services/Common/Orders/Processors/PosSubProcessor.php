<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\PaymentSplitTypes;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Models\SubOrderItem;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @author Darshit Hedpara
 */
class PosSubProcessor extends BaseProcessor
{
    protected string $createdFrom = 'pos';

    public function __construct()
    {
        parent::__construct();
    }

    protected function prepareOrderDetails()
    {
        parent::prepareOrderDetails();
        $this->orderData['split_no'] = $this->payload['order_index'];
    }

    protected function prepareOrderItemsDetails()
    {
        switch ($this->payload['split_payment_type']) {
            case PaymentSplitTypes::EQUAL_SPLIT:
                $this->equalSplitLogic();
                break;
            case PaymentSplitTypes::CUSTOM_SPLIT:
            	if(!isset($this->parentOrder->id)) {
            		parent::prepareOrderItemsDetails();
	            }
                $this->customSplitLogic();
                break;
            case PaymentSplitTypes::PRODUCT_SPLIT:
                parent::prepareOrderItemsDetails();
                break;
            default:
                break;
        }
    }

    protected function createOrder()
    {
    	companionLogger('Sub order create data : '. json_encode($this->orderData, JSON_PRETTY_PRINT));
        $this->createdOrder = SubOrder::query()->create($this->orderData);
    }

    protected function createOrderItems()
    {
        foreach ($this->orderItemsData as $key => $orderItem) {
            $orderItem['sub_order_id'] = $this->createdOrder->id;
            unset($orderItem['grab_and_go']);
            unset($orderItem['status']);
            $this->createdOrderItems[] = SubOrderItem::query()->insert($orderItem);
        }
    }

    protected function checkoutReservation()
    {
        if (isset($this->payload['is_last_payment']) && $this->payload['is_last_payment'] == 1) {
            if ($this->parentOrder) {
                $final_discount = SubOrder::query()->where('parent_order_id', $this->payload['parent_order_id'])
                    ->sum('coupon_price');
                $this->parentOrder->update([
                    'status'       => 'paid',
                    'paid_on'      => Carbon::now()->format('Y-m-d H:i:s'),
                    'coupon_price' => $final_discount,
                    'total_price'  => (float) $this->parentOrder->total_price - (float) $final_discount,
                ]);
                if (isset($this->parentOrder['parent_id']) && $this->parentOrder['parent_id'] != '') {
                    StoreReservation::query()->where('id', $this->parentOrder['parent_id'])->update([
                        'end_time'      => now()->format('H:i'),
                        'is_checkout'   => 1,
                        'checkout_from' => 'pos',
                    ]);
                    $reservation = StoreReservation::query()->where('id', $this->parentOrder['parent_id'])->first();
                    ReservationServeRequest::query()->where('reservation_id', $reservation->id)
                        ->where('is_served', 0)
                        ->update(['is_served' => 1]);
                    sendResWebNotification($reservation->id, $reservation->store_id, 'remove_booking');
                }
            }
        }
    }

    private function equalSplitLogic()
    {
        if ($this->parentOrder->payment_split_persons) {
            $this->orderData['discount_amount'] = ($this->parentOrder->discount_amount) ? ($this->parentOrder->discount_amount / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['discount_inc_tax'] = ($this->parentOrder->discount_inc_tax) ? ($this->parentOrder->discount_inc_tax / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['discount_type'] = ($this->parentOrder->discount_type) ? $this->parentOrder->discount_type : null;
            if (! empty($this->orderData['discount_type'])) {
                if ($this->orderData['discount_type'] == 'EURO') {
                    $this->orderData['discount'] = ($this->parentOrder->discount) ? ($this->parentOrder->discount / $this->parentOrder->payment_split_persons) : null;
                } else {
                    $this->orderData['discount'] = ($this->parentOrder->discount) ? $this->parentOrder->discount : null;
                }
            }
            $this->orderData['sub_total'] = $this->orderData['sub_total'] - $this->orderData['discount_amount'];
            if (! $this->parentOrder->subOrders->count()) {
                $rem_price = $this->parentOrder->total_price - ((floor($this->parentOrder->total_price * 100 / $this->parentOrder->payment_split_persons) / 100) * $this->parentOrder->payment_split_persons);
                $this->orderData['total_price'] = (floor($this->parentOrder->total_price * 100 / $this->parentOrder->payment_split_persons) / 100) + $rem_price;
            } else {
                $this->orderData['total_price'] = ($this->parentOrder->total_price) ? ($this->parentOrder->total_price / $this->parentOrder->payment_split_persons) : 0;
                $this->orderData['total_price'] = floor($this->orderData['total_price'] * 100) / 100;
            }
	        $total_21_amount = 0;
	        $total_21_product_deposit = 0;
	        collect($this->parentOrder->orderItems)->each(function ($q) use(&$total_21_amount, &$total_21_product_deposit){
		        if($q->tax_percentage == 21){
			        $total_21_amount += $q->total_price - $q->statiege_deposite_total;
			        $total_21_product_deposit += $q->statiege_deposite_total;
		        };
	        });
	        $total_9_amount = $this->parentOrder->total_price - $total_21_amount-$this->parentOrder->statiege_deposite_total - $this->parentOrder->tip_amount;

	        $total_9_amount = ($total_9_amount / $this->parentOrder->payment_split_persons);
	        $total_21_amount = ($total_21_amount / $this->parentOrder->payment_split_persons);
	        $statiege_deposite_total = ($this->parentOrder->statiege_deposite_total / $this->parentOrder->payment_split_persons);

	        $this->orderData['alcohol_product_total'] = $total_21_amount;
	        $this->orderData['normal_product_total'] = $total_9_amount;
	        $this->orderData['statiege_deposite_total'] = $statiege_deposite_total;
	        $this->orderData['total_price'] = $total_21_amount + $total_9_amount;
	        $this->orderData['total_alcohol_tax'] = $total_21_amount * 21 / 121;
	        $this->orderData['total_tax'] = $total_9_amount * 9 / 109;
	        $this->orderData['sub_total'] = $this->orderData['total_price'] - $this->orderData['total_alcohol_tax'] - $this->orderData['total_tax'];
	        $this->orderData['original_order_total'] = $this->orderData['total_price'] + $this->orderData['discount_inc_tax'];
	        $this->orderData['alcohol_sub_total'] = $total_21_amount - $this->orderData['total_alcohol_tax'];
	        $this->orderData['normal_sub_total'] = $total_9_amount - $this->orderData['total_tax'];
        }
    }

    private function customSplitLogic()
    {
        $amount = $this->payload['amount'];
        if(isset($this->parentOrder->total_price)) {
	        $total_amount = $this->parentOrder->total_price;

	        $total_21_amount = 0;
	        $total_21_product_deposit = 0;
	        collect($this->parentOrder->orderItems)->each(function ($q) use(&$total_21_amount, &$total_21_product_deposit){
		        if($q->tax_percentage == 21){
			        $total_21_amount += $q->total_price - $q->statiege_deposite_total;
			        $total_21_product_deposit += $q->statiege_deposite_total;
		        };
	        });
	        $total_9_amount = $this->parentOrder->total_price - $total_21_amount - $this->parentOrder->statiege_deposite_total - $this->parentOrder->tip_amount;

	        //division index
	        $dIndex = $amount / $total_amount;
	        $this->orderData['discount_amount'] = ($this->parentOrder->discount_amount) ? round($this->parentOrder->discount_amount * $dIndex, 2) : 0;
	        $this->orderData['discount_inc_tax'] = ($this->parentOrder->discount_inc_tax) ? round($this->parentOrder->discount_inc_tax * $dIndex, 2) : 0;
	        $this->orderData['discount_type'] = $this->parentOrder->discount_type ?? null;
	        if (! empty($this->orderData['discount_type'])) {
		        if ($this->orderData['discount_type'] == 'EURO') {
			        $this->orderData['discount'] = ($this->parentOrder->discount) ? round($this->parentOrder->discount * $dIndex, 2) : null;
		        } else {
			        $this->orderData['discount'] = $this->parentOrder->discount ?? null;
		        }
	        }
	        $this->orderData['sub_total'] = $this->orderData['sub_total'] - $this->orderData['discount_amount'];
	        $this->orderData['total_price'] = ($this->parentOrder->total_price) ? round($this->parentOrder->total_price * $dIndex, 2) : 0;
	        $this->orderData['total_price'] = floor($this->orderData['total_price'] * 100) / 100;

	        $total_9_amount = ($total_9_amount * $dIndex);
	        $total_21_amount = ($total_21_amount * $dIndex);
	        $statiege_deposite_total = ($this->parentOrder->statiege_deposite_total * $dIndex);

	        $this->orderData['alcohol_product_total'] = $total_21_amount ;
	        $this->orderData['normal_product_total'] = $total_9_amount;
	        $this->orderData['statiege_deposite_total'] = $statiege_deposite_total;
	        $this->orderData['total_tax'] = $total_9_amount * 9 / 109;
	        $this->orderData['total_alcohol_tax'] = $total_21_amount * 21 / 121;
	        $this->orderData['sub_total'] = $this->orderData['total_price'] - $this->orderData['total_tax'] - $this->orderData['total_alcohol_tax'];
	        $this->orderData['alcohol_sub_total'] = $total_9_amount - $this->orderData['total_tax'];
	        $this->orderData['normal_sub_total'] = $total_21_amount - $this->orderData['total_alcohol_tax'];
	        $this->orderData['original_order_total'] = $this->orderData['total_price'] + $this->orderData['discount_inc_tax'];

        }  else {
	        /*Get prev suborders total price*/
	        $reservation = StoreReservation::where('id', $this->payload['reservation_id'])->first();
	        $sub_orders = SubOrder::where('reservation_id', $this->payload['reservation_id'])->where('status', 'paid');
	        $sub_order_total_price = $sub_orders->sum('total_price');
	        if($sub_order_total_price > $this->payload['total_price']) {
		        $dIndex = $amount / $sub_order_total_price;
		        $this->orderData['sub_total'] = $sub_orders->sum('sub_total') * $dIndex;
		        $this->orderData['alcohol_sub_total'] = $sub_orders->sum('alcohol_sub_total') * $dIndex;
		        $this->orderData['normal_sub_total'] = $sub_orders->sum('normal_sub_total') * $dIndex;
		        $this->orderData['total_tax'] = $sub_orders->sum('total_tax') * $dIndex;
		        $this->orderData['total_alcohol_tax'] = $sub_orders->sum('total_alcohol_tax') * $dIndex;
		        $this->orderData['discount_amount'] = $sub_orders->sum('discount_amount') * $dIndex;
		        $this->orderData['discount_inc_tax'] = $sub_orders->sum('discount_inc_tax') * $dIndex;
		        if (isset($this->orderData['discount_type']) && $this->orderData['discount_type'] == 'EURO'){
			        $this->orderData['discount'] = ($reservation->discount) ? round($reservation->discount * $dIndex, 2) : null;
		        } else {
			        $this->orderData['discount'] = ($reservation->discount) ? $reservation->discount : null;
		        }
		        $this->orderData['normal_product_total'] = $sub_orders->sum('normal_product_total') * $dIndex;
		        $this->orderData['alcohol_product_total'] = $sub_orders->sum('alcohol_product_total') * $dIndex;
		        $this->orderData['statiege_deposite_total'] = $sub_orders->sum('statiege_deposite_total') * $dIndex;
		        $this->orderData['total_price'] = $amount;
		        $this->orderData['original_order_total'] = $this->orderData['total_price'] - $this->orderData['discount_inc_tax'];
	        }
	        else {
		        $dIndex = $amount / $this->payload['total_price'];
		        $order_data['sub_total'] = ($this->orderData['sub_total']) ? round($this->orderData['sub_total'] * $dIndex, 2)
			        : 0;
		        $this->orderData['alcohol_sub_total'] = ($this->orderData['alcohol_sub_total']) ? round($this->orderData['alcohol_sub_total'] * $dIndex, 2) : 0;
		        $this->orderData['normal_sub_total'] = ($this->orderData['normal_sub_total']) ? round($this->orderData['normal_sub_total'] * $dIndex, 2) : 0;
		        $this->orderData['total_tax'] = ($this->orderData['total_tax']) ? round($this->orderData['total_tax'] * $dIndex, 2) : 0;
		        $this->orderData['total_alcohol_tax'] = ($this->orderData['total_alcohol_tax']) ? round($this->orderData['total_alcohol_tax'] * $dIndex, 2) : 0;
		        $this->orderData['discount_amount'] = ($this->orderData['discount_amount']) ? round($this->orderData['discount_amount'] * $dIndex, 2) : 0;
		        $this->orderData['discount_inc_tax'] = ($this->orderData['discount_inc_tax']) ? round($this->orderData['discount_inc_tax'] * $dIndex, 2) : 0;
		        if (isset($this->orderData['discount_type']) && $this->orderData['discount_type'] == 'EURO'){
			        $this->orderData['discount'] = ($reservation->discount) ? round($reservation->discount * $dIndex, 2) : null;
		        } else {
			        $this->orderData['discount'] = ($reservation->discount) ? $reservation->discount : null;
		        }
		        $this->orderData['normal_product_total'] = ($sub_orders->sum('normal_product_total') * $dIndex);
		        $this->orderData['alcohol_product_total'] = ($sub_orders->sum('alcohol_product_total') * $dIndex);
		        $this->orderData['statiege_deposite_total'] = ($sub_orders->sum('statiege_deposite_total') * $dIndex);
		        $this->orderData['total_price'] = $amount;
		        $this->orderData['original_order_total'] = $this->orderData['total_price'] - $this->orderData['discount_inc_tax'];
	        }
        }
    }
}
