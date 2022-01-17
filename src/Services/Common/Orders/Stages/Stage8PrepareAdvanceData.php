<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\cartTotalValueCalc;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\discountCalc;
use function Weboccult\EatcardCompanion\Helpers\generatePOSOrderId;

/**
 * @description Stag 8
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage8PrepareAdvanceData
{
    /**
     * @return void
     */
    protected function prepareOrderDiscount()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (! empty($this->storeReservation)) {
                if (isset($this->storeReservation->discount_type) && isset($this->storeReservation->discount) && ! empty($this->storeReservation->discount) && (float) $this->storeReservation->discount > 0) {
                    $this->discountData['order_discount'] = $this->payload['order_discount'] = (float) $this->storeReservation->discount;
                    $this->discountData['is_euro_discount'] = $this->payload['is_euro_discount'] = $this->storeReservation->discount_type == 'EURO' ? 1 : 0;

                    if ($this->isSubOrder) {
                        $cart_original_total_for_euro_dis_calc = cartTotalValueCalc($this->getOriginalCart(), $this->productData, $this->storeReservation, $this->orderData['ayce_price']);
                        $current_split_cart_total_for_euro_dis_calc = cartTotalValueCalc($this->getCart(), $this->productData, $this->storeReservation, $this->orderData['ayce_price']);

                        // if same quantity is not in sub order then need to calculate euro discount dived it base on value
                        if ($this->discountData['is_euro_discount'] == 1 & $this->discountData['order_discount'] > 0 && $cart_original_total_for_euro_dis_calc > 0 && $cart_original_total_for_euro_dis_calc != $current_split_cart_total_for_euro_dis_calc) {
                            $this->discountData['order_discount'] = $this->payload['order_discount'] = round($this->discountData['order_discount'] * ($current_split_cart_total_for_euro_dis_calc / $cart_original_total_for_euro_dis_calc), 5);
                        }
                    }
                }
            }
        }
    }

    protected function preparePaymentMethod()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            $this->orderData['method'] = $this->payload['method'];
            if (isset($this->payload['manual_pin']) && $this->payload['manual_pin'] == 1) {
                $this->orderData['method'] = 'manual_pin';
            }
        }
        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['payment_method_type'] = $this->payload['type'];
        }
    }

    protected function preparePaymentDetails()
    {
        if (in_array($this->system, [SystemTypes::KIOSK, SystemTypes::POS, SystemTypes::WAITRESS])) {
            if ($this->orderData['method'] == 'cash') {
                $this->orderData['status'] = 'paid';
                $this->orderData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
                $this->orderData['payment_method_type'] = '';
            } else {
                $this->orderData['method'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
                $this->orderData['payment_method_type'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
            }
        }
    }

    protected function prepareSplitPaymentDetails()
    {
        if ($this->system == SystemTypes::POS) {
            if (isset($this->payload['is_split_payment']) && $this->payload['is_split_payment'] == 1) {
                $this->orderData['payment_split_persons'] = $this->payload['payment_split_persons'] ?? '';
                $this->orderData['payment_split_type'] = $this->payload['payment_split_type'] ?? '';
                $this->orderData['status'] = 'pending';
                $this->orderData['paid_on'] = null;
            }
        }
    }

    protected function prepareOrderId()
    {
        if ($this->system == SystemTypes::POS) {
            $order = null;
            if (isset($order_data['parent_id'])) {
                $order = Order::query()->where('parent_id', $order_data['parent_id'])->first();
            }
            $this->orderData['order_id'] = (! empty($order)) ? $order->order_id : generatePOSOrderId($this->store->id);
        }
    }

    protected function prepareOrderDetails()
    {
        $this->orderData['sub_total'] = 0;
        $this->orderData['alcohol_sub_total'] = 0;
        $this->orderData['normal_sub_total'] = 0;
        $this->orderData['total_tax'] = 0;
        $this->orderData['total_alcohol_tax'] = 0;
        $this->orderData['total_price'] = 0;
        $this->orderData['discount_amount'] = 0;
        $this->orderData['is_takeaway_mail_send'] = 0;
        $this->orderData['discount_inc_tax'] = 0;
        $this->orderData['statiege_deposite_total'] = 0;
        $this->orderData['ayce_price'] = 0;

        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (isset($this->payload['ayce_amount']) && ! empty($this->payload['ayce_amount'])) {
                $this->orderData['ayce_price'] = $this->payload['ayce_amount'];
            }
        }
    }

    protected function prepareSupplementDetails()
    {
    }

    protected function prepareOrderItemsDetails()
    {
        $normalOrder = (isset($this->payload['reservation_id']) && $this->payload['reservation_id'] != '' && ! empty($this->storeReservation)) ? 0 : 1;

        $current_cart_total_for_euro_dis_calc = cartTotalValueCalc($this->getCart(), $this->productData, $this->storeReservation, $this->orderData['ayce_price']);

        foreach ($this->getCart() as $key => $item) {
            /*This variable used for checked product and it/s supplement price count or not in total price*/
            $is_product_chargeable = true;

            $is_euro_discount = isset($item['is_euro_discount']) ? (int) $item['is_euro_discount'] : 0;

            // original quantity will be used only for sub order.
            $original_quantity = isset($item['original_quantity']) ? (int) $item['original_quantity'] : 0;
            $item_quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            $item_discount = isset($item['discount']) ? (float) $item['discount'] : 0;

            if ($this->isSubOrder) {

                // if same quantity is not in sub order then need to calculate euro discount
                if ($is_euro_discount == 1 & $this->discountData['order_discount'] == 0 && $original_quantity > 0 && $original_quantity != $item_quantity) {
                    $this->orderItemsData['discount'] = ($item_discount / $original_quantity) * $item_quantity;
                }
            }

            $this->orderItemsData[$key]['unit_price'] = 0;
            $this->orderItemsData[$key]['comment'] = $item['comment'] ?? '';
            $product = $this->productData->where('id', $item['id'])->first();

            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS]) && ! empty($this->storeReservation)) {
                $allYouCanEatPrice = 0;
                if ((isset($product->ayce_class) && ! empty($product->ayce_class)) && $product->ayce_class->count() > 0 && $this->storeReservation->dineInPrice && isset($this->storeReservation->dineInPrice->dinein_category_id) && $this->storeReservation->dineInPrice->dinein_category_id != '') {
                    $ayeClasses = $product->ayce_class->pluck('dinein_category_id')->toArray();
                    if (! empty($ayeClasses) && in_array($this->storeReservation->dineInPrice->dinein_category_id, $ayeClasses)) {
                        $allYouCanEatIndividualPrice = $product->ayce_class->where('dinein_category_id', $this->storeReservation->dineInPrice->dinein_category_id)
                            ->pluck('price');
                        if (isset($allYouCanEatIndividualPrice[0]) && $allYouCanEatIndividualPrice[0] > 0 && ! empty($allYouCanEatIndividualPrice[0]) && $product->all_you_can_eat_price >= 0) {
                            $allYouCanEatPrice = $allYouCanEatIndividualPrice[0];
                        } else {
                            if (! empty($product->all_you_can_eat_price)) {
                                $allYouCanEatPrice = $product->all_you_can_eat_price;
                            }
                        }
                    }
                } else {
                    /*If res type is cart then get product price from pieces*/
                    if (isset($product->total_pieces) && $product->total_pieces != '' && isset($product->pieces_price) && $product->pieces_price != '' && $this->storeReservation->reservation_type != 'all_you_eat') {
                        $allYouCanEatPrice = (float) $product->pieces_price;
                        // set_product_pieces_in_name($product, $is_need_update);
                        $this->orderItemsData[$key]['product_name'] = $product->name;
                    }
                }
                companionLogger('Product ayce price', $allYouCanEatPrice);
                if ($allYouCanEatPrice) {
                    //if there is ayce price
                    $product->price = $allYouCanEatPrice;
                }
            }
            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
                if (! $item['base_price']) {
                    $product->price = 0;
                    $is_product_chargeable = false;
                }
            } else {
                // default case
                $product->price = ((! empty($product->discount_price) && $product->discount_price > 0) && $product->discount_show) ? $product->discount_price : $product->price;
            }
            companionLogger('Product price', $product->price);
            $supplement_total = 0;
            $this->orderItemsData[$key]['void_id'] = $item['void_id'] ?? 0;
            $this->orderItemsData[$key]['on_the_house'] = $item['on_the_house'] ?? 0;
            $notVoided = ! (isset($item['void_id']) && $item['void_id'] != '');
            $notOnTheHouse = ! (isset($item['on_the_house']) && $item['on_the_house'] == '1');
            $this->orderItemsData[$key]['supplement_total'] = 0;
            $productTax = (isset($product->tax) && $product->tax != '') ? $product->tax : $product->category->tax;
            $this->orderItemsData[$key]['tax_percentage'] = $productTax;
            if (isset($item['supplements'])) {
                $finalSupplements = [];
                foreach (collect($item['supplements']) as $i) {
                    $isExist = collect($finalSupplements)->search(function ($item) use ($i) {
                        return $item['id'] == $i['id'] && $item['val'] == $i['val'];
                    });
                    if ($isExist && $i['val'] == $finalSupplements[$isExist]['val']) {
                        $finalSupplements[$isExist]['qty'] += 1;
                        $finalSupplements[$isExist]['total_val'] = $finalSupplements[$isExist]['val'] * $finalSupplements[$isExist]['qty'];
                    } else {
                        $currentSup = collect($this->supplementData)->where('id', $i['id'])->first();
                        $currentPreparedSupplement = [
                            'id'         => $i['id'],
                            'name'       => $i['name'],
                            'val'        => $i['val'] ?? 0,
                            'total_val'  => $i['val'] ?? 0,
                            'qty'        => isset($i['qty']) && $i['qty'] ? $i['qty'] : 1,
                            'categoryId' => isset($i['categoryId']) ? (int) $i['categoryId'] : null,
                            'alt_name'   => isset($currentSup->alt_name) && ! empty($currentSup->alt_name) ? $currentSup->alt_name : null,
                        ];
                        $finalSupplements[] = $currentPreparedSupplement;
                    }
                }
                $item['supplements'] = $finalSupplements;
            } else {
                $item['supplements'] = [];
            }
            foreach ($item['supplements'] as $supp) {
                $currentSup = collect($this->supplementData)->where('id', $supp['id'])->first();
                if ($supp['val'] != 0) {
                    $this->orderItemsData[$key]['supplement_total'] += $currentSup->price * $supp['qty'];
                    $supplement_total += $currentSup->price * $supp['qty'];
                }
            }
            $size_total = 0;
            if (isset($item['size']) && $item['size']) {
                if ($item['size']['name'] == 'large') {
                    $size_total = $product->large_price;
                } elseif ($item['size']['name'] == 'regular') {
                    $size_total = $product->regular_price;
                }
            } else {
                $item['size'] = [];
            }
            $weight_total = $product->price;
            if (isset($item['weight']) && $item['weight']) {
                $weight_total = ((int) $item['weight'] * $product->price) / $product->weight;
                $item['weight'] = [
                    'item_weight'    => $item['weight'],
                    'product_weight' => $product->weight,
                ];
            } else {
                $item['weight'] = [];
            }
            if ($is_product_chargeable) {
                $product_total = ($supplement_total + $size_total + $weight_total) * $item['quantity'];
            } else {
                $product_total = 0;
            }
            $this->orderItemsData[$key]['subtotal_inc_tax'] = $product_total;
            $this->orderItemsData[$key]['total_tax_amount'] = 0;
            $this->orderItemsData[$key]['normal_sub_total'] = 0;
            $this->orderItemsData[$key]['alcohol_sub_total'] = 0;
            if ($productTax == 21) {
                //21% tax
                $current_sub = ($product_total * $productTax / 121);
                $this->orderItemsData[$key]['subtotal_wo_tax'] = $product_total - $current_sub;
                $this->orderItemsData[$key]['alcohol_tax_amount'] = $current_sub;
                $this->orderItemsData[$key]['total_tax_amount'] += $current_sub;
                $this->orderItemsData[$key]['alcohol_sub_total'] = $product_total - $current_sub;
                if ($notVoided && $notOnTheHouse) {
                    $this->orderData['alcohol_sub_total'] += $product_total - $current_sub;
                    if (isset($item['discount']) && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                        $this->orderData['total_alcohol_tax'] += ($current_sub - discountCalc($product_total, $current_sub, $is_euro_discount, $item['discount']));
                    } else {
                        $this->orderData['total_alcohol_tax'] += $current_sub;
                    }
                }
            } else {
                //9% tax
                $current_sub = ($product_total * $productTax / 109);
                $this->orderItemsData[$key]['normal_sub_total'] = $product_total - $current_sub;
                $this->orderItemsData[$key]['subtotal_wo_tax'] = $product_total - $current_sub;
                $this->orderItemsData[$key]['normal_tax_amount'] = $current_sub;
                $this->orderItemsData[$key]['total_tax_amount'] += $current_sub;
                if ($notVoided && $notOnTheHouse) {
                    $this->orderData['normal_sub_total'] += $product_total - $current_sub;
                    if (isset($item['discount']) && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                        $this->orderData['total_tax'] += ($current_sub - discountCalc($product_total, $current_sub, $is_euro_discount, $item['discount']));
                    } else {
                        $this->orderData['total_tax'] += $current_sub;
                    }
                }
            }
            if ($notVoided && $notOnTheHouse) {
                $this->orderData['total_price'] += $product_total;
            }
            $this->orderItemsData[$key]['sub_total'] = $this->orderItemsData[$key]['normal_sub_total'] + $this->orderItemsData[$key]['alcohol_sub_total'];
            $this->orderItemsData[$key]['unit_price'] = $product->price;
            /*calculate discount for each product*/
            $this->orderItemsData[$key]['discount'] = null;
            $this->orderItemsData[$key]['discount_type'] = null;
            $this->orderItemsData[$key]['discount_price'] = 0;
            $this->orderItemsData[$key]['discount_amount_wo_tax'] = 0;
            $this->orderItemsData[$key]['discount_inc_tax'] = 0;
            if (isset($item['discount']) && (float) $item['discount'] > 0 && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                $this->orderItemsData[$key]['discount'] = $item['discount'];
                $this->orderItemsData[$key]['discount_type'] = ($is_euro_discount == 1) ? 'EURO' : '%';
                $this->orderItemsData[$key]['discount_price'] = discountCalc($product_total, ($product_total - $current_sub), $is_euro_discount, $item['discount']);
                $this->orderItemsData[$key]['discount_amount_wo_tax'] = $this->orderItemsData[$key]['discount_price'];
                $this->orderItemsData[$key]['discount_inc_tax'] = discountCalc($product_total, $product_total, $is_euro_discount, $item['discount']);
                if ($notVoided && $notOnTheHouse) {
                    $this->orderData['discount_inc_tax'] += $this->orderItemsData[$key]['discount_inc_tax'];
                    $this->orderData['discount_amount'] += $this->orderItemsData[$key]['discount_price'];
                }
            } elseif (isset($this->discountData['order_discount']) && (float) $this->discountData['order_discount'] > 0) {
                //order general discount add in all order items
                if ($notVoided && $notOnTheHouse) {
                    $this->orderItemsData[$key]['discount'] = ($this->discountData['is_euro_discount'] == 1) ? round(discountCalc($product_total, $product_total, $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc), 2) : $this->discountData['order_discount'];
                    $this->orderItemsData[$key]['discount_type'] = ($this->discountData['is_euro_discount'] == 1) ? 'EURO' : '%';
                    $this->orderItemsData[$key]['discount_price'] = discountCalc($product_total, ($product_total - $current_sub), $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc);
                    $this->orderItemsData[$key]['discount_amount_wo_tax'] = $this->orderItemsData[$key]['discount_price'];
                    $this->orderItemsData[$key]['discount_inc_tax'] = discountCalc($product_total, $product_total, $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc);
                    $this->orderData['discount_inc_tax'] += $this->orderItemsData[$key]['discount_inc_tax'];
                }
            }
            $transferItems = [];
            if (isset($item['isTransfer']) && $item['isTransfer'] == 1) {
                $this->orderItemsData[$key]['transfer'] = 1;
                $transferItems['isTransfer'] = $item['isTransfer'];
                $transferItems['sourceReservationId'] = $item['sourceReservationId'] ?? null;
                $transferItems['destinationReservationId'] = $item['destinationReservationId'] ?? null;
                $transferItems['sourceTableId'] = $item['sourceTableId'] ?? null;
                $transferItems['destinationTableId'] = $item['destinationTableId'] ?? null;
                $transferItems['sourceTableName'] = $item['sourceTableName'] ?? null;
                $transferItems['destinationTableName'] = $item['destinationTableName'] ?? null;
                $transferItems['forRoundNumber'] = $item['forRoundNumber'] ?? null;
            }
            $this->orderItemsData[$key]['product_id'] = $product->id;
            $this->orderItemsData[$key]['product_name'] = $product->name;
            $this->orderItemsData[$key]['quantity'] = $item['quantity'];
            $this->orderItemsData[$key]['extra'] = json_encode([
                'serve_type'  => $item['serve_type'] ?? [],
                'size'        => $item['size'] ?? [],
                'supplements' => $item['supplements'] ?? [],
                'weight'      => $item['weight'] ?? [],
                'users'       => $item['user_name'] ?? [],
                'transfer'    => $transferItems,
            ]);
            $this->orderItemsData[$key]['total_price'] = ($this->orderItemsData[$key]['sub_total'] - $this->orderItemsData[$key]['discount_price']) + (($this->orderItemsData[$key]['sub_total'] - $this->orderItemsData[$key]['discount_price']) * $this->orderItemsData[$key]['tax_percentage'] / 100);
            if ($normalOrder && $notVoided && $notOnTheHouse) {
                $deposit = $product->statiege_id_deposite ?? 0;
                $this->orderItemsData[$key]['statiege_deposite_value'] = $deposit;
                $this->orderItemsData[$key]['statiege_deposite_total'] = $deposit * $item['quantity'];
                $this->orderItemsData[$key]['total_price'] += $deposit * $item['quantity'];
                $this->orderData['statiege_deposite_total'] += $deposit * $item['quantity'];
            }
            if (isset($item['status'])) {
                $this->orderItemsData[$key]['status'] = $item['status'];
            } else {
                $this->orderItemsData[$key]['status'] = 'received';
            }
            if ($this->system == SystemTypes::POS && isset($this->payload['reservation_id']) && $this->payload['reservation_id']) {
                $this->orderItemsData[$key]['status'] = 'done';
            }
            $this->orderItemsData[$key]['comment'] = isset($item['comment']) && ! empty($item['comment']) ? $item['comment'] : null;
        }

        $this->orderData['sub_total'] = $this->orderData['normal_sub_total'] + $this->orderData['alcohol_sub_total'];
        $this->orderData['total_price'] = $this->orderData['sub_total'] + $this->orderData['total_tax'] + $this->orderData['total_alcohol_tax'];
    }

    protected function calculateOrderDiscount()
    {
        if (isset($this->discountData['order_discount']) && $this->discountData['order_discount'] > 0) {
            $this->orderData['discount_type'] = ($this->discountData['is_euro_discount'] == 1) ? 'EURO' : '%';
            $this->orderData['discount'] = $this->discountData['order_discount'];
            $this->orderData['discount_amount'] = discountCalc($this->orderData['total_price'], $this->orderData['sub_total'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']);
            $this->orderData['discount_inc_tax'] = discountCalc($this->orderData['total_price'], $this->orderData['total_price'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']);
            $this->orderData['total_tax'] = ($this->orderData['total_tax'] - discountCalc($this->orderData['total_price'], $this->orderData['total_tax'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']));
            $this->orderData['total_alcohol_tax'] = ($this->orderData['total_alcohol_tax'] - discountCalc($this->orderData['total_price'], $this->orderData['total_alcohol_tax'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']));
        }
        $this->orderData['total_price'] = $this->orderData['total_price'] - $this->orderData['discount_amount'];
    }

    protected function prepareAllYouCanEatAmountDetails()
    {
        if (isset($this->payload['ayce_amount']) && $this->payload['ayce_amount']) {
            $product_total = $this->payload['ayce_amount'];
            $this->orderData['ayce_price'] = $this->payload['ayce_amount'];
            $current_sub = ($product_total * 9 / 109);
            $this->orderData['normal_sub_total'] += $product_total - $current_sub;
            $this->orderData['total_tax'] += $current_sub;
            $this->orderData['total_price'] += $product_total;
        }
    }

    protected function calculateFees()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            if ($this->settings['delivery_fee']['status']) {
                $this->orderData['delivery_fee'] = $this->settings['delivery_fee']['value'];
            }
            $this->orderData['total_price'] += $this->orderData['delivery_fee'];
        }
        if ($this->orderData['method'] != 'cash' && isset($store->storeSetting) && $store->storeSetting->is_pin == 1 && $store->storeSetting->additional_fee) {
            $this->orderData['additional_fee'] = $store->storeSetting->additional_fee;
            $this->orderData['total_price'] += $this->orderData['additional_fee'];
        }
    }

    protected function calculateStatiegeDeposite()
    {
        if ($this->system == SystemTypes::POS) {
            $normalOrder = (isset($this->payload['reservation_id']) && $this->payload['reservation_id'] != '' && ! empty($this->storeReservation)) ? 0 : 1;
            if ($normalOrder) {
                $this->orderData['total_price'] += $this->orderData['statiege_deposite_total'];
            }
        }
    }

    protected function calculateReservationPaidAmount()
    {
        $reservationPaidAmount = 0;
        if (isset($this->storeReservation) && $this->storeReservation && isset($this->storeReservation->payment_status) && $this->storeReservation->payment_status == 'paid') {
            if ($this->storeReservation->total_price) {
                $reservationPaidAmount = $this->storeReservation->total_price;
            }
        }
        $this->orderData['total_price'] = $this->orderData['total_price'] - $reservationPaidAmount;
        $this->orderData['reservation_paid'] = $reservationPaidAmount;
    }

    protected function prepareEditOrderDetails()
    {
        if ($this->system == SystemTypes::POS) {
            if (isset($this->payload['edited']) && $this->payload['edited'] == 1) {
                $this->orderData['is_edited'] = 1;
                $this->orderData['edited_by'] = $this->payload['edited_by'] ?? '';
                $this->orderData['ref_id'] = $this->payload['ref_id'] ?? '';
                $this->orderData['is_base_order'] = 0;
            }
        }
    }
}
