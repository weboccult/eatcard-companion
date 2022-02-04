<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Enums\PaymentSplitTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\ReservationServeRequest;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Models\SubOrderItem;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\eatcardOrder;
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
        switch ($this->parentOrder->payment_split_type) {
            case PaymentSplitTypes::EQUAL_SPLIT:
                $this->equalSplitLogic();
                break;
            case PaymentSplitTypes::CUSTOM_SPLIT:
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
        $this->createdOrder = SubOrder::query()->create($this->orderData);
    }

    protected function createOrderItems()
    {
        foreach ($this->orderItemsData as $key => $orderItem) {
            $orderItem['sub_order_id'] = $this->createdOrder->id;
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
                    ReservationOrderItem::query()->where('reservation_id', $reservation->id)->update(['split_payment_status' => 0]);
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
            $this->orderData['sub_total'] = ($this->parentOrder->sub_total) ? ($this->parentOrder->sub_total / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['alcohol_sub_total'] = ($this->parentOrder->alcohol_sub_total) ? ($this->parentOrder->alcohol_sub_total / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['normal_sub_total'] = ($this->parentOrder->normal_sub_total) ? ($this->parentOrder->normal_sub_total / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['total_tax'] = ($this->parentOrder->total_tax) ? ($this->parentOrder->total_tax / $this->parentOrder->payment_split_persons) : 0;
            $this->orderData['total_alcohol_tax'] = ($this->parentOrder->total_alcohol_tax) ? ($this->parentOrder->total_alcohol_tax / $this->parentOrder->payment_split_persons) : 0;
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
        }
    }

    private function customSplitLogic()
    {
        $amount = $this->payload['amount'];

        if (! empty($this->parentOrder)) {
            $total_amount = $this->parentOrder->total_price;

            $total_21_amount = 0;
            $total_21_product_deposit = 0;
            collect($this->parentOrder->orderItems)->each(function ($q) use (&$total_21_amount, &$total_21_product_deposit) {
                if ($q->tax_percentage == 21) {
                    $total_21_amount += $q->total_price - $q->statiege_deposite_total;
                    $total_21_product_deposit += $q->statiege_deposite_total;
                }
            });
            $total_9_amount = $this->parentOrder->total_price - $total_21_amount - $this->parentOrder->statiege_deposite_total;

            //division index
            $dIndex = $amount / $total_amount;
            $this->orderData['sub_total'] = ($this->parentOrder->sub_total) ? round($this->parentOrder->sub_total * $dIndex, 2) : 0;
            $this->orderData['alcohol_sub_total'] = ($this->parentOrder->alcohol_sub_total) ? round($this->parentOrder->alcohol_sub_total * $dIndex, 2) : 0;
            $this->orderData['normal_sub_total'] = ($this->parentOrder->normal_sub_total) ? round($this->parentOrder->normal_sub_total * $dIndex, 2) : 0;
            $this->orderData['total_tax'] = ($this->parentOrder->total_tax) ? round($this->parentOrder->total_tax * $dIndex, 2) : 0;
            $this->orderData['total_alcohol_tax'] = ($this->parentOrder->total_alcohol_tax) ? round($this->parentOrder->total_alcohol_tax * $dIndex, 2) : 0;
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

            $this->orderData['normal_product_total'] = $total_9_amount * $dIndex;
            $this->orderData['alcohol_product_total'] = $total_21_amount * $dIndex;
            $this->orderData['statiege_deposite_total'] = $this->parentOrder->statiege_deposite_total * $dIndex;
        } elseif (! empty($this->storeReservation)) {
            try {
                $reservationRound = $this->storeReservation->rounds()->get();
                $cartData = [];
                collect($reservationRound)->map(function ($item) use (&$cartData) {
                    $reservation_cart_data = json_decode($item['cart'], true);
                    foreach ($reservation_cart_data as $cart_data) {
                        $cartData[] = $cart_data;
                    }
                });
                $payload = [
                    'reservation_id' => $this->storeReservation,
                    'store_id' => $this->store->id,
                    'table_id' => $this->table->id,
                    'device_id' => $this->payload['device_id'],
                ];

                $parentData = eatcardOrder()
                            ->processor(PosProcessor::class)
                            ->system(SystemTypes::POS)
                            ->payload($payload)
                            ->cart($cartData)
                            ->simulate()
                            ->dispatch();

                $total_amount = $parentData['order_data']['total_price'];
                $total_21_amount = $parentData['order_data']['total_21_amount'];
                $total_9_amount = $parentData['order_data']['total_price'] - $total_21_amount - $parentData['order_data']['statiege_deposite_total'];

                //division index
                $dIndex = $amount / $total_amount;
                $this->orderData['sub_total'] = ($parentData['order_data']['sub_total']) ? round($parentData['order_data']['sub_total'] * $dIndex, 2) : 0;
                $this->orderData['alcohol_sub_total'] = ($parentData['order_data']['alcohol_sub_total']) ? round($parentData['order_data']['alcohol_sub_total'] * $dIndex, 2) : 0;
                $this->orderData['normal_sub_total'] = ($parentData['order_data']['normal_sub_total']) ? round($parentData['order_data']['normal_sub_total'] * $dIndex, 2) : 0;
                $this->orderData['total_tax'] = ($parentData['order_data']['total_tax']) ? round($parentData['order_data']['total_tax'] * $dIndex, 2) : 0;
                $this->orderData['total_alcohol_tax'] = ($parentData['order_data']['total_alcohol_tax']) ? round($parentData['order_data']['total_alcohol_tax'] * $dIndex, 2) : 0;
                $this->orderData['discount_amount'] = ($parentData['order_data']['discount_amount']) ? round($parentData['order_data']['discount_amount'] * $dIndex, 2) : 0;
                $this->orderData['discount_inc_tax'] = ($parentData['order_data']['discount_inc_tax']) ? round($parentData['order_data']['discount_inc_tax'] * $dIndex, 2) : 0;
                $this->orderData['discount_type'] = $parentData['order_data']['discount_type'] ?? null;
                if (! empty($this->orderData['discount_type'])) {
                    if ($this->orderData['discount_type'] == 'EURO') {
                        $this->orderData['discount'] = ($parentData['order_data']['discount']) ? round($parentData['order_data']['discount'] * $dIndex, 2) : null;
                    } else {
                        $this->orderData['discount'] = $parentData['order_data']['discount'] ?? null;
                    }
                }
                $this->orderData['sub_total'] = $this->orderData['sub_total'] - $this->orderData['discount_amount'];
                $this->orderData['total_price'] = ($parentData['order_data']['total_price']) ? round($parentData['order_data']['total_price'] * $dIndex, 2) : 0;
                $this->orderData['total_price'] = floor($this->orderData['total_price'] * 100) / 100;
                $this->orderData['normal_product_total'] = $total_9_amount * $dIndex;
                $this->orderData['alcohol_product_total'] = $total_21_amount * $dIndex;
                $this->orderData['statiege_deposite_total'] = $parentData['order_data']['statiege_deposite_total'] * $dIndex;
            } catch (Exception $e) {
                $this->setDumpDieValue(['error' => 'Something went wrong while preparing parent order data.!']);
            }
        } else {
            $this->setDumpDieValue(['error' => 'parent order and reservation both not found.! sub order can\'t be create.!']);
        }
    }
}
