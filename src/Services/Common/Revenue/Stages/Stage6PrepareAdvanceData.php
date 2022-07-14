<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Models\DrawerCount;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;

/**
 * @description Stag Stage6PrepareAdvanceData
 * @mixin BaseGenerator
 */
trait Stage6PrepareAdvanceData
{
    protected function prepareOrderDateRelatedDetails()
    {

        //get data from order
        Order::select(
            'id',
            'store_id',
            'is_ignored',
            'parent_id',
            'order_id',
            'created_at',
            'paid_on',
            'status',
            'order_date',
            'order_status',
            'order_type',
            'kiosk_id',
            'method',
            'payment_method_type',
            'total_price',
            'normal_sub_total',
            'alcohol_sub_total',
            'discount_amount',
            'total_tax',
            'total_alcohol_tax',
            'discount',
            'statiege_deposite_total',
            'thusibezorgd_order_id',
            'uber_eats_order_id',
            'is_paylater_order',
            'deliveroo_order_id'
        )
            ->with(['orderItems' => function ($q1) {
                $q1->with(['product' => function ($q2) {
                    $q2->withTrashed()->with(['category' => function ($q3) {
                        $q3->withTrashed();
                    }]);
                }]);
            }, 'voidOrder' => function ($q5) {
                $q5->where('restore_status', 0);
            }, 'subOrders' => function ($q4) {
                $q4->where('status', 'paid')/*->where('method', 'cash')*/ ->where('total_price', '!=', 0);
            }])
            ->where('store_id', $this->storeId)
//            ->where('order_date', $this->date)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->where('order_date', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('order_date', $this->month);
                $qDateMonth->whereYear('order_date', $this->year);
            })
//            ->where('status', 'paid')
            ->where(function ($q6) {
                $q6->orWhere('status', 'paid');
                $q6->orWhere('is_paylater_order', 1);
            })
            ->where('is_ignored', 0)
            ->chunk(500, function ($orders) use (&$count) {
                $this->calculateOrderDateRelatedData($orders);
            });

        //get data from order
        OrderHistory::select(
            'id',
            'store_id',
            'is_ignored',
            'parent_id',
            'order_id',
            'created_at',
            'paid_on',
            'status',
            'order_date',
            'order_status',
            'order_type',
            'kiosk_id',
            'method',
            'payment_method_type',
            'total_price',
            'normal_sub_total',
            'alcohol_sub_total',
            'discount_amount',
            'total_tax',
            'total_alcohol_tax',
            'discount',
            'statiege_deposite_total',
            'thusibezorgd_order_id',
            'uber_eats_order_id',
            'is_paylater_order',
            'deliveroo_order_id'
        )
            ->with(['orderItems' => function ($q1) {
                $q1->with(['product' => function ($q2) {
                    $q2->withTrashed()->with(['category' => function ($q3) {
                        $q3->withTrashed();
                    }]);
                }]);
            }, 'voidOrder' => function ($q5) {
                $q5->where('restore_status', 0);
            }, 'subOrders' => function ($q4) {
                $q4->where('status', 'paid')/*->where('method', 'cash')*/ ->where('total_price', '!=', 0);
            }])->where('store_id', $this->storeId)
//                ->where('order_date', $this->date)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->where('order_date', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('order_date', $this->month);
                $qDateMonth->whereYear('order_date', $this->year);
            })
//            ->where('status', 'paid')
            ->where(function ($q6) {
                $q6->orWhere('status', 'paid');
                $q6->orWhere('is_paylater_order', 1);
            })
            ->where('is_ignored', 0)
            ->chunk(500, function ($orders) use (&$count) {
                $this->calculateOrderDateRelatedData($orders);
            });
    }

    protected function prepareOrderCreateDateRelatedDetails()
    {

        //get data from order
        Order::select(
            'id',
            'is_ignored',
            'sub_total',
            'discount_inc_tax',
            'discount_inc_tax_legacy',
            'discount_type',
            'tip_amount',
            'is_base_order',
            'reservation_paid',
            'payment_split_type',
            'statiege_deposite_total',
            'all_you_eat_data',
            'parent_id',
            'store_id',
            'order_id',
            'created_at',
            'paid_on',
            'order_date',
            'status',
            'order_status',
            'order_type',
            'total_price',
            'kiosk_id',
            'normal_sub_total',
            'alcohol_sub_total',
            'discount_amount',
            'total_tax',
            'total_alcohol_tax',
            'discount',
            'method',
            'thusibezorgd_order_id',
            'uber_eats_order_id',
            'payment_method_type',
            'coupon_price',
            'delivery_fee',
            'additional_fee',
            'plastic_bag_fee',
            'deliveroo_order_id'
        )
            ->with([
                'orderItems' => function ($q1) {
                    $q1->with([
                        'product' => function ($q2) {
                            $q2->withTrashed()->with([
                                'category' => function ($q3) {
                                    $q3->withTrashed();
                                },
                            ]);
                        },
                    ]);
                },
                'voidOrder'  => function ($q5) {
                    $q5->where('restore_status', 0);
                },
                'subOrders'  => function ($q4) {
                    $q4->where('status', 'paid')/*->where('method', 'cash')*/ ->where('total_price', '!=', 0);
                },
            ])
            ->where('store_id', $this->storeId)
            //            ->where('order_date', $this->date)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->whereDate('paid_on', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('paid_on', $this->month);
                $qDateMonth->whereYear('paid_on', $this->year);
            })
//            ->where('status', 'paid')
            ->where(function ($q6) {
                $q6->orWhere('status', 'paid');
                $q6->orWhere('is_paylater_order', 1);
            })
            ->where('is_ignored', 0)
            ->chunk(500, function ($orders) use (&$count) {
                $this->calculateOrderCreateDateRelatedData($orders);
            });
        //get data from order
        OrderHistory::select(
            'id',
            'is_ignored',
            'sub_total',
            'discount_inc_tax',
            'discount_inc_tax_legacy',
            'discount_type',
            'tip_amount',
            'is_base_order',
            'reservation_paid',
            'payment_split_type',
            'statiege_deposite_total',
            'all_you_eat_data',
            'parent_id',
            'store_id',
            'order_id',
            'created_at',
            'paid_on',
            'order_date',
            'status',
            'order_status',
            'order_type',
            'total_price',
            'kiosk_id',
            'normal_sub_total',
            'alcohol_sub_total',
            'discount_amount',
            'total_tax',
            'total_alcohol_tax',
            'discount',
            'method',
            'thusibezorgd_order_id',
            'uber_eats_order_id',
            'payment_method_type',
            'coupon_price',
            'delivery_fee',
            'additional_fee',
            'plastic_bag_fee',
            'deliveroo_order_id'
        )
            ->with([
                'orderItems' => function ($q1) {
                    $q1->with([
                        'product' => function ($q2) {
                            $q2->withTrashed()->with([
                                'category' => function ($q3) {
                                    $q3->withTrashed();
                                },
                            ]);
                        },
                    ]);
                },
                'voidOrder'  => function ($q5) {
                    $q5->where('restore_status', 0);
                },
                'subOrders'  => function ($q4) {
                    $q4->where('status', 'paid')/*->where('method', 'cash')*/ ->where('total_price', '!=', 0);
                },
            ])
            ->where('store_id', $this->storeId)
            //                ->where('order_date', $this->date)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->whereDate('paid_on', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('paid_on', $this->month);
                $qDateMonth->whereYear('paid_on', $this->year);
            })
//            ->where('status', 'paid')
            ->where(function ($q6) {
                $q6->orWhere('status', 'paid');
                $q6->orWhere('is_paylater_order', 1);
            })
            ->where('is_ignored', 0)
            ->chunk(500, function ($orders) use (&$count) {
                $this->calculateOrderCreateDateRelatedData($orders);
            });
    }

    protected function prepareGiftPurchaseOrderDetails()
    {
        $gift_card_price = GiftPurchaseOrder::select([
                   'id', 'status', 'created_at', 'store_id', 'total_price',
               ])
            ->where('store_id', $this->storeId)
            ->where('status', 'paid')
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->whereDate('created_at', $this->date);
            })
           ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
               $qDateMonth->whereMonth('created_at', $this->month);
               $qDateMonth->whereYear('created_at', $this->year);
           })
            ->get();

        foreach ($gift_card_price as $giftCard) {
            $giftCardTotalPrice = (float) ($giftCard->total_price ?? 0);
            $giftCardDate = Carbon::parse($giftCard->created_at)->format('Y-m-d');

            $this->calcData['total_gift_card_count_date'][$giftCardDate] += 1;
            $this->calcData['total_gift_card_amount_date'][$giftCardDate] += $giftCardTotalPrice;
        }
    }

    protected function prepareDrawerCountDetails()
    {
        $this->calcData['number_of_cashdrawer_open'] = DrawerCount::query()
            ->where('store_id', $this->storeId)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->whereDate('created_at', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('created_at', $this->month);
                $qDateMonth->whereYear('created_at', $this->year);
            })
            ->sum('count');
    }

    protected function prepareStoreReservationDetails()
    {
        /*Count reservation received amount*/
        StoreReservation::where('store_id', $this->storeId)
            ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
                $qDate->whereDate('paid_on', $this->date);
            })
            ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
                $qDateMonth->whereMonth('paid_on', $this->month);
                $qDateMonth->whereYear('paid_on', $this->year);
            })
            ->whereIn('payment_status', ['paid', 'partial_refunded', 'refunded'])
            ->chunk(500, function ($reservations) {
                foreach ($reservations as $reservation) {
                    $this->calcData['reservation_received_total_date'][Carbon::parse($reservation->paid_on)->format('Y-m-d')] += (float) ($reservation->original_total_price ?? 0);
                }
            });

        StoreReservation::where('store_id', $this->storeId)
           ->where('is_refunded', 1)
           ->when($this->revenueType == RevenueTypes::DAILY, function ($qDate) {
               $qDate->whereDate('refund_price_date', $this->date);
           })
           ->when($this->revenueType == RevenueTypes::MONTHLY, function ($qDateMonth) {
               $qDateMonth->whereMonth('refund_price_date', $this->month);
               $qDateMonth->whereYear('refund_price_date', $this->year);
           })
           ->chunk(500, function ($reservations) {
               foreach ($reservations as $reservation) {
//                   $this->calcData['reservation_received_total_date'][Carbon::parse($reservation->refund_price_date)->format('Y-m-d')] -= (float) ($reservation->refund_price ?? 0);
                   $this->calcData['reservation_refund_total_date'][Carbon::parse($reservation->refund_price_date)->format('Y-m-d')] += (float) ($reservation->refund_price ?? 0);
               }
           });
    }

    /**
     * @param $orders
     *
     * @return void
     */
    private function calculateOrderDateRelatedData($orders)
    {
        foreach ($orders as $order) {

            //set third party flag
            $isThirdPartyOrder = false;
            if (! empty($order->thusibezorgd_order_id) || ! empty($order->uber_eats_order_id) || ! empty($order->deliveroo_order_id)) {
                $isThirdPartyOrder = true;
            }

            //calc manual void order after create
            if (isset($order->voidOrder) && isset($order->voidOrder[0]) && isset($order->voidOrder[0]->order_id)) {
                $this->calcData['void_order'] += 1;
                continue;
            }

            // count cash and card pay orders
            if ($order->method == 'cash') {
                $this->calcData['total_cash_orders'] += 1;
            } elseif (in_array($order->method, ['wipay', 'ccv', 'manual_pin',  'pin'])) {
                $this->calcData['total_pin_orders'] += 1;
            }

            //calc online pay orders
            if (in_array($order->payment_method_type, ['multisafepay', 'mollie'])) {
                $this->calcData['total_ideal_orders'] += 1;
            }

            if (in_array($order->order_date, $this->calcData['dates'])) {

                //count third party orders
                if (! empty($order->thusibezorgd_order_id)) {
                    $this->calcData['thusibezorgd_orders'] += 1;
                }

                if (! empty($order->uber_eats_order_id)) {
                    $this->calcData['ubereats_orders'] += 1;
                }

                if (! empty($order->deliveroo_order_id)) {
                    $this->calcData['deliveroo_orders'] += 1;
                }

                //calc product counts
                if ($isThirdPartyOrder) {
                    $this->calcData['third_party_product_count'] += collect($order['orderItems'])->sum('quantity');
                } else {
                    $this->calcData['product_count'] += collect($order['orderItems'])->sum('quantity');
                }

                //calc total orders
                if (in_array($order->order_type, ['pickup', 'delivery', 'dine_in', 'all_you_eat']) && ! $isThirdPartyOrder) {
                    $this->calcData['total_orders'] += 1;
                } elseif ($order->kiosk_id != null && $order->order_type != 'dine_in' && $order->order_type != 'all_you_eat' && ! $isThirdPartyOrder) {
                    $this->calcData['total_orders'] += 1;
                }
            } else {
                if (in_array($order->order_date, $this->calcData['future_date_arr'])) {
                    $this->calcData['total_orders'] += 1;
                }
            }
        }
    }

    /**
     * @param $orders
     *
     * @return void
     */
    private function calculateOrderCreateDateRelatedData($orders)
    {
        foreach ($orders as $order) {
            $orderTotalPrice = (float) ($order->total_price ?? 0);
            $orderDate = Carbon::parse($order->paid_on)->format('Y-m-d');
            $tip_amount = (float) ($order->tip_amount ?? 0);

            //as per discussion not need to calculate tip amount in turnover
            $orderTotalPrice -= $tip_amount;

            //set third party flag
            $isThirdPartyOrder = false;
            if (! empty($order->thusibezorgd_order_id) || ! empty($order->uber_eats_order_id) || ! empty($order->deliveroo_order_id)) {
                $isThirdPartyOrder = true;
            }

            //set reservation amount less in bill
            if ($order->is_base_order == 1) {
                $this->calcData['reservation_deducted_total'] += (float) ($order->reservation_paid ?? 0);
            }

            //takeaway order price
            if (! $isThirdPartyOrder && in_array($order->order_type, ['pickup', 'delivery'])) {
                $this->calcData['total_takeaway'] += $orderTotalPrice;
            }

            //dine in price
            if (! $isThirdPartyOrder && in_array($order->order_type, ['dine_in', 'all_you_eat'])) {
                $this->calcData['total_dine_in'] += $orderTotalPrice;
            }

//           if (!$isThirdPartyOrder && in_array($order->order_type,['pickup','delivery','dine_in','all_you_eat'])) {
//               $this->calcData['final_revenue'] += $orderTotalPrice;
//           }

            //total pin, manual pin payments | exclude sub orders
            if (empty($order->payment_split_type) && in_array($order->method, ['wipay', 'ccv', 'manual_pin',  'pin'])) {
                $this->calcData['total_pin_amount'] += $orderTotalPrice;
            }

            //total online payments
            if (empty($order->payment_split_type) && in_array($order->payment_method_type, ['multisafepay', 'mollie'])) {
                $this->calcData['total_ideal_amount'] += $orderTotalPrice;
            }

            //total cash payments | exclude sub orders
            if (empty($order->payment_split_type) && $order->method == 'cash') {
                $this->calcData['total_cash_amount'] += $orderTotalPrice;
            }
            //total tip amount

            $this->calcData['total_tip_amount'] += $tip_amount;

            //total cash and pin payments for sub orders
            if (isset($order['subOrders']) && count($order['subOrders']) > 0) {
                $this->calcData['total_pin_amount'] += collect($order['subOrders'])
                   ->where('status', 'paid')
                   ->where('method', '!=', 'cash')
                   ->sum('total_price');

                $subOrderPinTipAmount = collect($order['subOrders'])
                                   ->where('status', 'paid')
                                   ->where('method', '!=', 'cash')
                                   ->sum('tip_amount');

                $this->calcData['total_pin_amount'] -= $subOrderPinTipAmount;

                $this->calcData['total_cash_amount'] += collect($order['subOrders'])
                   ->where('status', 'paid')
                   ->where('method', 'cash')
                   ->sum('total_price');

                $subOrderCashTipAmount = collect($order['subOrders'])
                                   ->where('status', 'paid')
                                   ->where('method', 'cash')
                                   ->sum('tip_amount');

                $this->calcData['total_cash_amount'] -= $subOrderCashTipAmount;
            }

            //total product deposit
            $this->calcData['deposite_total'] += (float) ($order->statiege_deposite_total ?? 0);

            //calculate on-the-house amount
            if ($order['all_you_eat_data'] != null) {
                $res_ayce_data = json_decode($order->all_you_eat_data, true);
                if (isset($res_ayce_data['house'])) {
                    $on_the_house_adult = (int) (isset($res_ayce_data['adult']) ? $res_ayce_data['adult'] : 0) * (float) $res_ayce_data['dinein_price']['price'];
                    $on_the_house_kid2 = (int) (isset($res_ayce_data['kid2']) ? $res_ayce_data['kid2'] : 0) * (float) $res_ayce_data['dinein_price']['child_price_2'];
                    $on_the_house_kid1 = 0;
                    if (isset($res_ayce_data['on_the_house_kids_age']) && $res_ayce_data['dinein_price']['is_per_year'] && count($res_ayce_data['on_the_house_kids_age']) > 0) {
                        foreach ($res_ayce_data['on_the_house_kids_age'] as $child_age) {
                            $age_difference = ($child_age - $res_ayce_data['dinein_price']['min_age']) + 1;
                            $on_the_house_kid1 += (isset($res_ayce_data['dinein_price']['child_price']) ? (int) $res_ayce_data['dinein_price']['child_price'] : 0) * $age_difference;
                        }
                    } else {
                        $on_the_house_kid1 = (int) (isset($res_ayce_data['kid1']) ? $res_ayce_data['kid1'] : 0) * (float) $res_ayce_data['dinein_price']['child_price'];
                    }
                    $dynamic_price_class_on_the_house_price = 0;
                    if (isset($res_ayce_data['dinein_price']['dynamic_prices']) && ! empty($res_ayce_data['dinein_price']['dynamic_prices'])) {
                        foreach ($res_ayce_data['dinein_price']['dynamic_prices'] as $dynamic_price_class) {
                            $dynamic_price_class_on_the_house_price += (float) $dynamic_price_class['price'] * (isset($dynamic_price_class['on_the_house_person']) ? (int) $dynamic_price_class['on_the_house_person'] : 0);
                        }
                    }
                    $this->calcData['on_the_house_item_total'] += $on_the_house_adult + $on_the_house_kid2 + $on_the_house_kid1 + $dynamic_price_class_on_the_house_price;
                }
            }

            //calculate delivery fee | exclude third party orders
            if (! empty($order->delivery_fee) && ! $isThirdPartyOrder) {
                $this->calcData['delivery_fee_total'] += $order->delivery_fee;
                $this->calcData['delivery_fee_total_date'][$orderDate] += (float) ($order->delivery_fee);
            }

            //calculate additional fee | exclude third party orders
            if (! empty($order->additional_fee) && ! $isThirdPartyOrder) {
                $this->calcData['additional_fee_total'] += $order->additional_fee;
//              $this->calcData['additional_fee_total_date'][$orderDate] += (float)($order->additional_fee);
            }

            //calculate plastic bag fee | exclude third party orders
            if (! empty($order->plastic_bag_fee) && ! $isThirdPartyOrder) {
                $this->calcData['plastic_bag_fee_total'] += $order->plastic_bag_fee;
                $this->calcData['plastic_bag_fee_total_date'][$orderDate] += (float) ($order->plastic_bag_fee);
            }

            //calculate thusibezorgd order amount
            if (! empty($order->thusibezorgd_order_id)) {
                $this->calcData['thusibezorgd_orders_amount'] += $orderTotalPrice;
            }

            //calculate thusibezorgd order amount
            if (! empty($order->uber_eats_order_id)) {
                $this->calcData['ubereats_orders_amount'] += $orderTotalPrice;
            }

            if (! empty($order->deliveroo_order_id)) {
                $this->calcData['deliveroo_orders_amount'] += $orderTotalPrice;
            }

            //calculate thusibezorgd order amount
            if (! empty($order->coupon_price)) {
                $this->calcData['coupon_used_price'] += (float) ($order->coupon_price ?? 0);
                $this->calcData['coupon_used_price_date'][$orderDate] += (float) ($order->coupon_price ?? 0);
            }

            //discount inc tax reverse calc (discount_inc_tax_legacy) not possible before 2021-06-01
            if (Carbon::parse($order->paid_on) >= Carbon::parse('2021-12-02 10:10:00')) {
                $this->calcData['total_discount_inc_tax_date'][$orderDate] += (float) ($order->discount_inc_tax ?? 0);
            } elseif ($orderDate >= '2021-06-01') {
                $this->calcData['total_discount_inc_tax_date'][$orderDate] += (float) ($order->discount_inc_tax_legacy ?? 0);
            }

            //tax calculation for store orders | exclude third party orders
            if (! $isThirdPartyOrder && ($order->order_type == 'pickup' || $order->order_type == 'delivery' || $order->order_type == 'dine_in' || $order->order_type == 'all_you_eat' || in_array($order->kiosk_id, $this->additionalSettings['kiosk_device_id_array']))) {
                foreach ($order['orderItems'] as $item) {
                    if ($item['on_the_house'] == 1) {
                        $this->calcData['on_the_house_item_total'] += (float) ($item['subtotal_inc_tax'] ?? 0);
                    }
                }

                $this->calcData['total_amount_inc_tax_date'][$orderDate] += $orderTotalPrice;
                $this->calcData['total_amount_without_tax_date'][$orderDate] += $order['normal_sub_total'] + $order['alcohol_sub_total'];

                // from onwards 01-07-2021 we have separate tax columns so use it | rest all need to calculate manually
                if ($order['order_date'] >= '2020-07-01') {
                    $this->calcData['total_9_without_tax_subtotal_date'][$orderDate] += $order['normal_sub_total'];
                    $this->calcData['total_21_without_tax_subtotal_date'][$orderDate] += $order['alcohol_sub_total'];
//                   $this->calcData['total_discount_without_tax_date'][$orderDate] += $order['discount_amount'];
                    $this->calcData['total_9_tax_date'][$orderDate] += $order['total_tax'];
                    $this->calcData['total_21_tax_date'][$orderDate] += $order['total_alcohol_tax'];
                    if ($order['discount_type'] == '%') {
                        $total_amount_without_tax = $order['normal_sub_total'] + $order['alcohol_sub_total'];
                        if ($total_amount_without_tax > 0) {
                            $this->calcData['total_9_discount_without_tax_date'][$orderDate] += ($order['normal_sub_total'] * $order['discount_amount']) / $total_amount_without_tax;
                            $this->calcData['total_21_discount_without_tax_date'][$orderDate] += ($order['alcohol_sub_total'] * $order['discount_amount']) / $total_amount_without_tax;
                        }
                    } else {
                        foreach ($order['orderItems'] as $item) {
                            if ($item['on_the_house'] == 0 && empty($item['void_id']) && isset($item['normal_tax_amount'])) {
                                if ((float) $item['normal_tax_amount'] > 0) {
                                    $this->calcData['total_9_discount_without_tax_date'][$orderDate] += $item['discount_price'];
                                } elseif ((float) $item['alcohol_tax_amount'] > 0) {
                                    $this->calcData['total_21_discount_without_tax_date'][$orderDate] += $item['discount_price'];
                                }
                            }
                        }
                    }

                    //-------------------------------------------------------------
                    if (Carbon::parse($order['paid_on'])->format('Y-m-d') >= '2021-06-01') {
                        $this->calcData['total_discount_without_tax_date'][$orderDate] += (float) ($order['discount_amount']);
                    } else {
                        if ((isset($order['discount']) && (float) $order['discount'] > 0) && (isset($order['discount_amount']) && (float) $order['discount_amount'] > 0) && $order['order_type'] == 'pos') {
                            if ((int) $order['discount'] == 100) {
                                $total_discount_for_non_alcohol = (100 * $order['total_tax']) - $order['total_tax'];
                                $total_discount_for_alcohol = (100 * $order['total_alcohol_tax']) - $order['total_alcohol_tax'];
                            } else {
                                $total_discount_for_non_alcohol = (100 * $order['total_tax'] / (100 - (float) $order['discount'])) - $order['total_tax'];
                                $total_discount_for_alcohol = (100 * $order['total_alcohol_tax'] / (100 - (float) $order['discount'])) - $order['total_alcohol_tax'];
                            }
                            $this->calcData['total_discount_without_tax_date'][$orderDate] += $total_discount_for_alcohol + $total_discount_for_non_alcohol + $order['discount_amount'];
                        } else {
                            foreach ($order['orderItems'] as $item) {
                                if ($item['on_the_house'] == 0 && empty($item['void_id'])) {
                                    if (Carbon::parse($order->paid_on)->format('Y-m-d') >= '2021-06-01') {
                                        $this->calcData['total_discount_without_tax_date'][$orderDate] += (float) $item['discount_price'];
                                    } else {
                                        if ((float) $item['discount_price'] > 0 && (isset($item['discount']) && $item['discount'] != '' && (float) $item['discount'] > 0)) {
                                            $product_total = (float) $item['discount_price'] * 100 / (float) $item['discount'];
                                            if (isset($item['product']['category']['tax'])) {
                                                if ($item['product']['category']['tax'] == 9) {
                                                    $product_total = $product_total * 1.09;
                                                } else {
                                                    $product_total = $product_total * 1.21;
                                                }
                                                $this->calcData['total_discount_without_tax_date'][$orderDate] += ($product_total * (float) $item['discount']) / 100;
                                            } else {
                                                $this->calcData['total_discount_without_tax_date'][$orderDate] += $item['discount_price'] ? (float) $item['discount_price'] : 0;
                                            }
                                        } elseif (isset($item['discount_price']) && (float) $item['discount_price'] > 0) {
                                            $this->calcData['total_discount_without_tax_date'][$orderDate] += (float) $item['discount_price'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //-------------------------------------------------------------
                } else {
                    foreach ($order['order_items'] as $item) {
                        $extra = json_decode($item['extra']);
                        if (isset($item['product']['category']) && isset($item['product']['category']['tax'])) {
                            if ($extra && $extra->supplements) {
                                foreach ($extra->supplements as $supplement) {
                                    $item['unit_price'] += isset($supplement->val) ? (float) $supplement->val : 0;
                                }
                            }
                            if ($extra && $extra->size && $extra->size->price) {
                                $item['unit_price'] += (float) $extra->size->price;
                            }
                            $temp = $item['unit_price'] * $item['quantity'];
                            if ($item['product']['category']['tax'] == 9) {
                                $this->calcData['total_9_tax_date'][$orderDate] += ($temp * 9) / 100;
                                $this->calcData['total_9_without_tax_subtotal_date'][$orderDate] += ($temp - (($temp * 9) / 100));
                            } else {
                                $this->calcData['total_21_tax_date'][$orderDate] += ($temp * 21) / 100;
                                $this->calcData['total_21_without_tax_subtotal_date'][$orderDate] += ($temp - ($temp * 21) / 100);
                            }
                            $this->calcData['total_discount_without_tax_date'][$orderDate] += (($temp * $order['discount']) / 100);
                        }
                    }
                }

                //total kiosk|pos device wise
                foreach ($this->store->kioskDevices as $kiosk_device) {
                    if ($order->kiosk_id == $kiosk_device->id && ! in_array($order['order_type'], ['all_you_eat', 'dine_in'])) {
                        $this->calcData['total_kiosk'] += $orderTotalPrice;
//                       $this->calcData['kioskTotal'][$kiosk_device->id] += round($orderTotalPrice,2);

                        $orderTotalCloneForSubtractGrabAndGoItemPrice = $orderTotalPrice;
                        foreach ($order->orderItems as $odrItem){
                            if ($odrItem->grab_and_go == 1){
                                $this->calcData['grab_and_go_total_amount'] += $odrItem->total_price;
                                $orderTotalCloneForSubtractGrabAndGoItemPrice -= $odrItem->total_price;
                            }
                        }

                        $this->calcData['kioskTotal'][$kiosk_device->name] += $orderTotalCloneForSubtractGrabAndGoItemPrice;
                        $this->calcData['store_device_amount'][$kiosk_device->name] += $orderTotalPrice;
                    }
                }
            } elseif ($isThirdPartyOrder) {
                $this->calcData['total_third_party_total_date'][$orderDate] += $orderTotalPrice;
            }
        }
    }

    protected function prepareSummary()
    {
        foreach ($this->calcData['dates'] as $date) {

           //add Plastic-bag, Delivery fee changes in 21% tax
            $plastic_bag_21_tax = ($this->calcData['plastic_bag_fee_total_date'][$date] * 21) / 121;
            $delivery_fee_21_tax = ($this->calcData['delivery_fee_total_date'][$date] * 21) / 121;

            //final tax calc
            $this->calcData['total_21_tax_date'][$date] += ($plastic_bag_21_tax + $delivery_fee_21_tax);
            $this->calcData['total_tax_date'][$date] += $this->calcData['total_9_tax_date'][$date] + $this->calcData['total_21_tax_date'][$date];

            //update 21 % subtotal
            $this->calcData['total_21_without_tax_subtotal_date'][$date] += ($this->calcData['plastic_bag_fee_total_date'][$date] - $plastic_bag_21_tax)
                                                                                   + ($this->calcData['delivery_fee_total_date'][$date] - $delivery_fee_21_tax);

            $this->calcData['total_third_party_total'] += $this->calcData['total_third_party_total_date'][$date];
            $this->calcData['total_amount_inc_tax'] += $this->calcData['total_amount_inc_tax_date'][$date];
            $this->calcData['total_amount_without_tax'] += $this->calcData['total_amount_without_tax_date'][$date];
            $this->calcData['total_9_without_tax_subtotal'] += $this->calcData['total_9_without_tax_subtotal_date'][$date];
            $this->calcData['total_21_without_tax_subtotal'] += $this->calcData['total_21_without_tax_subtotal_date'][$date];
            $this->calcData['total_9_tax'] += $this->calcData['total_9_tax_date'][$date];
            $this->calcData['total_21_tax'] += $this->calcData['total_21_tax_date'][$date];
            $this->calcData['total_tax'] += $this->calcData['total_tax_date'][$date];
            $this->calcData['total_discount_inc_tax'] += $this->calcData['total_discount_inc_tax_date'][$date];
            $this->calcData['total_discount_without_tax'] += $this->calcData['total_discount_without_tax_date'][$date];
            $this->calcData['total_9_discount_without_tax'] += $this->calcData['total_9_discount_without_tax_date'][$date];
            $this->calcData['total_21_discount_without_tax'] += $this->calcData['total_21_discount_without_tax_date'][$date];
//            $this->calcData['coupon_used_price'] += $this->calcData['coupon_used_price_date'][$date];
            $this->calcData['total_gift_card_count'] += $this->calcData['total_gift_card_count_date'][$date];
            $this->calcData['total_gift_card_amount'] += $this->calcData['total_gift_card_amount_date'][$date];

            $this->calcData['reservation_received_total'] += $this->calcData['reservation_received_total_date'][$date];
            $this->calcData['reservation_refund_total'] += $this->calcData['reservation_refund_total_date'][$date];

            //additional calculations

            //total income with product, gift-card, reservation free,
            $this->calcData['total_turn_over_with_tax_date'][$date] += $this->calcData['total_amount_inc_tax_date'][$date] + $this->calcData['total_gift_card_amount_date'][$date] + $this->calcData['reservation_received_total_date'][$date] - $this->calcData['reservation_refund_total_date'][$date];
            if ($this->additionalSettings['third_party_revenue_status'] == 1) {
                $this->calcData['total_turn_over_with_tax_date'][$date] += $this->calcData['total_third_party_total_date'][$date];
            }
            $this->calcData['total_turn_over_with_tax'] += $this->calcData['total_turn_over_with_tax_date'][$date];

            $this->calcData['total_turn_over_without_tax_date'][$date] += $this->calcData['total_amount_without_tax_date'][$date]
                                                                        - $this->calcData['total_discount_without_tax_date'][$date]
                                                                        + ($this->calcData['plastic_bag_fee_total_date'][$date] - $plastic_bag_21_tax)
                                                                        + ($this->calcData['delivery_fee_total_date'][$date] - $delivery_fee_21_tax);
        }

        //additional calculations
        $this->calcData['total_0_without_tax_subtotal'] += $this->calcData['deposite_total'] + $this->calcData['total_gift_card_amount'];

        $this->calcData['total_9_without_tax_discount_subtotal'] += $this->calcData['total_9_without_tax_subtotal'] - $this->calcData['total_9_discount_without_tax'];
        $this->calcData['total_21_without_tax_discount_subtotal'] += $this->calcData['total_21_without_tax_subtotal'] - $this->calcData['total_21_discount_without_tax'];

        $this->calcData['total_9_inc_tax_without_discount_subtotal'] +=
           $this->calcData['total_9_without_tax_subtotal'] + $this->calcData['total_9_tax'] - $this->calcData['total_9_discount_without_tax'];
        $this->calcData['total_21_inc_tax_without_discount_subtotal'] +=
          $this->calcData['total_21_without_tax_subtotal'] + $this->calcData['total_21_tax'] - $this->calcData['total_21_discount_without_tax'];

        //all gift card always buy online payments
        $this->calcData['total_ideal_amount'] += $this->calcData['total_gift_card_amount'] + $this->calcData['reservation_received_total'] - $this->calcData['reservation_refund_total'];

        $this->calcData['final_total_amount'] += $this->calcData['total_takeaway']
                                         + $this->calcData['total_dine_in']
                                         + $this->calcData['total_kiosk']
                                         + $this->calcData['total_gift_card_amount']
                                         + $this->calcData['reservation_received_total']
                                         - $this->calcData['reservation_refund_total'];

        $this->calcData['final_total_orders'] += $this->calcData['total_orders'];
        $this->calcData['final_product_count'] += $this->calcData['product_count'];

        if ($this->additionalSettings['third_party_revenue_status'] == 1) {
            $this->calcData['final_total_amount'] += $this->calcData['thusibezorgd_orders_amount'] + $this->calcData['ubereats_orders_amount'] + $this->calcData['deliveroo_orders_amount'];
            $this->calcData['final_total_orders'] += $this->calcData['thusibezorgd_orders'] + $this->calcData['ubereats_orders'] + $this->calcData['deliveroo_orders'];
            $this->calcData['final_product_count'] += $this->calcData['third_party_product_count'];
        }

        $this->calcData['original_total_amount'] += $this->calcData['final_total_amount'] + $this->calcData['total_discount_inc_tax'] + $this->calcData['coupon_used_price'];

        if ($this->calcData['final_total_orders'] > 0) {
            $this->calcData['final_avg'] = $this->calcData['final_total_amount'] / $this->calcData['final_total_orders'];
        }

        $this->calcData['final_pin_ideal_amount'] = $this->calcData['total_pin_amount'] + $this->calcData['total_ideal_amount'];
        $this->calcData['final_pin_ideal_cash_amount'] = $this->calcData['final_pin_ideal_amount'] + $this->calcData['total_cash_amount'];
    }
}
