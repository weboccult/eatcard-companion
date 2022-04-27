<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\SubCategory;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\cartTotalValueCalc;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\discountCalc;
use function Weboccult\EatcardCompanion\Helpers\generateDineInOrderId;
use function Weboccult\EatcardCompanion\Helpers\generateKioskOrderId;
use function Weboccult\EatcardCompanion\Helpers\generatePOSOrderId;
use function Weboccult\EatcardCompanion\Helpers\generateTakeawayOrderId;
use function Weboccult\EatcardCompanion\Helpers\getUpdatedProductName;

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
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->discountData['order_discount'] = 0;
            if ($this->payload['order_type'] == 'pickup' && $this->takeawaySetting->is_pickup_discount) {
                $this->discountData['order_discount'] = $this->takeawaySetting->is_discount_enable ? $this->takeawaySetting->discount_rate : 0;
            }
            if ($this->payload['order_type'] == 'delivery' && $this->takeawaySetting->is_delivery_discount) {
                $this->discountData['order_discount'] = $this->takeawaySetting->is_discount_enable ? $this->takeawaySetting->discount_rate : 0;
            }
            $this->discountData['is_euro_discount'] = 0;
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
            $this->orderData['method'] = $this->payload['method'];
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
        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['is_paylater_order'] = 0;
            if (isset($this->payload['is_pay_later_order']) && $this->payload['is_pay_later_order'] != 1) {
                if ($this->payload['type'] == 'mollie') {
                    $this->orderData['payment_method_type'] = 'mollie';
                } elseif ($this->payload['type'] == 'multisafepay') {
                    $this->orderData['payment_method_type'] = 'multisafepay';
                } else {
                    $this->setDumpDieValue(['payment_type_not_valid' => 'error']);
                }
            } else {
                $this->orderData['method'] = '';
                $this->orderData['is_paylater_order'] = 1;
                $this->orderData['status'] = 'pending';
            }
            if ($this->orderData['method'] == 'cash') {
                $this->orderData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            }
            // $this->orderData['payment_method_type'] = $this->payload['type'];
        }
        if ($this->system == SystemTypes::KIOSK) {
            if (isset($this->payload['bop']) && ($this->payload['bop'] != '' || $this->payload['bop'] != null) && $this->payload['bop'] == 'wot@kiosk') {
                $this->orderData['status'] = 'paid';
                $this->orderData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            }
        }
        if ($this->system == SystemTypes::DINE_IN) {
            $this->orderData['payment_method_type'] = $this->payload['type'] ?? '';

            if (! $this->payload['method'] || $this->payload['method'] == 'cash' || $this->payload['method'] == 'pin') {
                $this->orderData['status'] = 'paid';
                $this->orderData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
                $this->orderData['payment_method_type'] = '';
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
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            $this->orderData['order_id'] = generatePOSOrderId($this->store->id);
        }
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->orderData['order_id'] = generateTakeawayOrderId($this->store->id);
        }
        if ($this->system == SystemTypes::KIOSK) {
            $this->orderData['order_id'] = generateKioskOrderId($this->store->id);
        }
        if ($this->system == SystemTypes::DINE_IN) {
            $this->orderData['order_id'] = generateDineInOrderId($this->store->id);
            if (isset($order_data['parent_id'])) {
                $order = Order::query()->where('parent_id', $this->orderData['parent_id'])->first();
                if (! empty($order)) {
                    $this->orderData['order_id'] = $order->order_id;
                }
            }
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

        if (isset($this->payload['comment'])) {
            $this->orderData['comment'] = $this->payload['comment'] ?? '';
        }
    }

    protected function prepareTipAmount()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS]) && ! empty($this->storeReservation)) {
            $this->orderData['tip_amount'] = $this->payload['tip_amount'] ?? 0;
            $subOrder = SubOrder::query()->where('reservation_id', $this->storeReservation->id)->get();

            if (isset($subOrder) && collect($subOrder)->count() > 0) {
                $this->orderData['tip_amount'] = collect($subOrder)->sum('tip_amount');
            }
        }
    }

    protected function prepareSupplementDetails()
    {
    }

    protected function prepareOrderItemsDetails()
    {
        $normalOrder = (isset($this->payload['reservation_id']) && $this->payload['reservation_id'] != '' && ! empty($this->storeReservation)) ? 0 : 1;

        $current_cart_total_for_euro_dis_calc = 0;
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            $current_cart_total_for_euro_dis_calc = cartTotalValueCalc($this->getCart(), $this->productData, $this->storeReservation, $this->orderData['ayce_price']);
        }

        foreach ($this->getCart() as $key => $item) {
            /*This variable used for checked product and it/s supplement price count or not in total price*/
            $is_product_chargeable = true;

            $is_euro_discount = isset($item['is_euro_discount']) ? (int) $item['is_euro_discount'] : 0;

            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS]) && $this->isSubOrder) {
                // original quantity will be used only for sub order.
                $original_quantity = isset($item['original_quantity']) ? (int) $item['original_quantity'] : 0;
                $item_quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
                $item_discount = isset($item['discount']) ? (float) $item['discount'] : 0;

                // if same quantity is not in sub order then need to calculate euro discount
                if ($is_euro_discount == 1 & $this->discountData['order_discount'] == 0 && $original_quantity > 0 && $original_quantity != $item_quantity) {
                    $this->orderItemsData['discount'] = ($item_discount / $original_quantity) * $item_quantity;
                }
            }

            $this->orderItemsData[$key]['unit_price'] = 0;
            $this->orderItemsData[$key]['comment'] = $item['comment'] ?? '';
            $product = $this->productData->where('id', $item['id'])->first();
            $productCalcPrice = 0;

            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS, SystemTypes::DINE_IN]) && ! empty($this->storeReservation)) {
                if ((isset($product->ayce_class) && ! empty($product->ayce_class)) && $product->ayce_class->count() > 0 && $this->storeReservation->dineInPrice && isset($this->storeReservation->dineInPrice->dinein_category_id) && $this->storeReservation->dineInPrice->dinein_category_id != '') {
                    $ayeClasses = $product->ayce_class->pluck('dinein_category_id')->toArray();
                    if (! empty($ayeClasses) && in_array($this->storeReservation->dineInPrice->dinein_category_id, $ayeClasses)) {
                        $allYouCanEatIndividualPrice = $product->ayce_class->where('dinein_category_id', $this->storeReservation->dineInPrice->dinein_category_id)
                            ->pluck('price');
                        if (isset($allYouCanEatIndividualPrice[0]) && $allYouCanEatIndividualPrice[0] > 0 && ! empty($allYouCanEatIndividualPrice[0]) && $product->all_you_can_eat_price >= 0) {
                            $productCalcPrice = $allYouCanEatIndividualPrice[0];
                        } else {
                            if (! empty($product->all_you_can_eat_price)) {
                                $productCalcPrice = $product->all_you_can_eat_price;
                            }
                        }
                    }
                } else {
                    /*If res type is cart then get product price from pieces*/
                    if (isset($product->total_pieces) && $product->total_pieces != '' && isset($product->pieces_price) && $product->pieces_price != '' && $this->storeReservation->reservation_type != 'all_you_eat') {
                        $productCalcPrice = (float) $product->pieces_price;
                        $product->show_pieces = 1;
                        // set_product_pieces_in_name($product, $is_need_update);
                    }
                }
                companionLogger('Product ayce price', $productCalcPrice);
            }

            //set product default price first
            $product->price = ((! empty($product->discount_price) && $product->discount_price > 0) && $product->discount_show) ? $product->discount_price : $product->price;

            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
                if ($productCalcPrice > 0) {
                    $product->price = $productCalcPrice;
                } elseif (! $item['base_price']) {
                    $product->price = 0;
                    $is_product_chargeable = false;
                }
            } elseif ($this->system === SystemTypes::DINE_IN) {

                /*
                 *  -> need to set product piece price for dine-in store and guest 1st order because the not have reservation
                    -> we need to set pieces price for all without reservation orders
                */
                if ($productCalcPrice > 0) {
                    $product->price = $productCalcPrice;
                } elseif (empty($this->storeReservation)) {
                    if (isset($product->is_al_a_carte) && $product->is_al_a_carte == 1 && isset($product->total_pieces) && $product->total_pieces != '' && isset($product->pieces_price) && $product->pieces_price != '') {
                        $product->price = (float) $product->pieces_price;
                        $product->show_pieces = 1;
                    }
                }
            } elseif ($this->system === SystemTypes::TAKEAWAY) {
                $product_price = $product->price;
                $product->price = (! is_null($product->discount_price) && $product->discount_show && ($this->orderData['order_type'] == 'pickup' || $this->orderData['order_type'] == 'delivery')) ? $product->discount_price : $product->price;
                /*discount related calculation based on from and to date*/
                if ($product->discount_price && ($this->orderData['order_type'] == 'pickup' || $this->orderData['order_type'] == 'delivery')) {
                    if (($product->from_date < $this->orderData['order_date'] && $product->to_date < $this->orderData['order_date']) || ($product->from_date > $this->orderData['order_date'] && $product->to_date > $this->orderData['order_date'])) {
                        if ($product->from_date && $product->to_date) {
                            /*use normal price*/
                            $product->price = $product_price;
                        } else {
                            /*use discount price*/
                            $product->price = $product->discount_price;
                        }
                    } else {
                        /*use discount price*/
                        $product->price = $product->discount_price;
                    }
                }
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

            $subCategories = [];
            if ($this->system === SystemTypes::KIOSK) {
                $sub_category_id = collect($item['supplements'])->pluck('category_id')->toArray();
                $subCategories = SubCategory::query()->whereIn('id', $sub_category_id)->get();
            }

            if (isset($item['supplements'])) {
                $finalSupplements = [];
                foreach (collect($item['supplements']) as $i) {
                    $isExist = collect($finalSupplements)->search(function ($item) use ($i) {
                        return $item['id'] == $i['id'] && $item['val'] == $i['val'];
                    });
                    if ($isExist > -1 && $i['val'] == $finalSupplements[$isExist]['val']) {
                        $finalSupplements[$isExist]['qty'] += 1;
                        $finalSupplements[$isExist]['total_val'] = $finalSupplements[$isExist]['val'] * $finalSupplements[$isExist]['qty'];
                    } else {
                        $currentSup = collect($this->supplementData)->where('id', $i['id'])->first();

                        $supQty = $i['qty'] ?? 1;
                        $supVal = $i['val'] ?? 0;
                        $supTotalVal = $i['total_val'] ?? 0;
                        if ($supTotalVal == 0) {
                            $supTotalVal = $supVal * $supQty;
                        }
                        $supCategoryId = (int) ($i['categoryId'] ?? $i['supplement_cat_id'] ?? 0);

                        $currentPreparedSupplement = [
                            'id'         => $i['id'],
                            'name'       => $i['name'],
                            'val'        => (float) $supVal,
                            'total_val'  => (float) $supTotalVal,
                            'qty'        => (int) $supQty,
                            'categoryId' => ! empty($supCategoryId) ? $supCategoryId : null,
                            'alt_name'   => isset($currentSup->alt_name) && ! empty($currentSup->alt_name) ? $currentSup->alt_name : null,
                        ];

                        if ($this->system === SystemTypes::KIOSK) {
                            $currentSubCategoryData = collect($subCategories)->firstWhere('id', $i['category_id']);
                            $currentPreparedSupplement['category_name'] = $currentSubCategoryData['name'] ?? '';
                            $currentPreparedSupplement['deselect_supplement'] = $currentSubCategoryData['display_deselected'] ?? '';
                        }

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

            if ($this->system === SystemTypes::DINE_IN) {
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
                    if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
                        if (isset($item['discount']) && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                            $this->orderData['total_alcohol_tax'] += ($current_sub - discountCalc($product_total, $current_sub, $is_euro_discount, $item['discount']));
                        } else {
                            $this->orderData['total_alcohol_tax'] += $current_sub;
                        }
                    }
                    if ($this->system === SystemTypes::TAKEAWAY) {
                        if (! $item['exclude_discount'] && $this->discountData['order_discount'] && (float)$this->discountData['order_discount'] > 0) {
                            $this->orderData['total_alcohol_tax'] += ($current_sub - (($current_sub * (float) $this->discountData['order_discount']) / 100));
                        } else {
                            $this->orderData['total_alcohol_tax'] += $current_sub;
                        }
                    }
                    if (in_array($this->system, [SystemTypes::KIOSK, SystemTypes::DINE_IN])) {
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
                    if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
                        if (isset($item['discount']) && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                            $this->orderData['total_tax'] += ($current_sub - discountCalc($product_total, $current_sub, $is_euro_discount, $item['discount']));
                        } else {
                            $this->orderData['total_tax'] += $current_sub;
                        }
                    }
                }
                if ($this->system === SystemTypes::TAKEAWAY) {
                    if (! $item['exclude_discount'] && $this->discountData['order_discount'] && (float)$this->discountData['order_discount'] > 0) {
                        $this->orderData['total_tax'] += ($current_sub - (($current_sub * (float)$this->discountData['order_discount']) / 100));
                    } else {
                        $this->orderData['total_tax'] += $current_sub;
                    }
                }
                if (in_array($this->system, [SystemTypes::KIOSK, SystemTypes::DINE_IN])) {
                    $this->orderData['total_tax'] += $current_sub;
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
            if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
                if (isset($item['discount']) && (float) $item['discount'] > 0 && (! isset($this->discountData['order_discount']) || ! $this->discountData['order_discount'] > 0)) {
                    $this->orderItemsData[$key]['discount'] = $item['discount'];
                    $this->orderItemsData[$key]['discount_type'] = ($is_euro_discount == 1) ? 'EURO' : '%';
                    $this->orderItemsData[$key]['discount_price'] = discountCalc($product_total, ($product_total - $current_sub), $is_euro_discount, $item['discount']);
                    $this->orderItemsData[$key]['discount_amount_wo_tax'] = $this->orderItemsData[$key]['discount_price'];
                    $this->orderItemsData[$key]['discount_inc_tax'] = discountCalc($product_total, $product_total, $is_euro_discount, $item['discount']);

                    if ($notVoided && $notOnTheHouse) {
                        $this->orderData['discount_amount'] += $this->orderItemsData[$key][$key]['discount_price'];
                        $this->orderData['discount_inc_tax'] += $this->orderItemsData[$key]['discount_inc_tax'];
                    }
                } elseif (isset($this->discountData['order_discount']) && (float) $this->discountData['order_discount'] > 0) {
                    //order general discount add in all order items
                    if ($notVoided && $notOnTheHouse) {
                        $this->orderItemsData[$key]['discount'] = ($this->discountData['is_euro_discount'] == 1) ? round(discountCalc($product_total, $product_total, $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc), 2) : $this->discountData['order_discount'];
                        $this->orderItemsData[$key]['discount_type'] = ($this->discountData['is_euro_discount'] == 1) ? 'EURO' : '%';
                        $this->orderItemsData[$key]['discount_price'] = discountCalc($product_total, ($product_total - $current_sub), $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc);
                        $this->orderItemsData[$key]['discount_amount_wo_tax'] = $this->orderItemsData[$key]['discount_price'];
                        $this->orderItemsData[$key]['discount_inc_tax'] = discountCalc($product_total, $product_total, $this->discountData['is_euro_discount'], $this->discountData['order_discount'], $current_cart_total_for_euro_dis_calc);
                    }
                }
            }
            if ($this->system === SystemTypes::TAKEAWAY) {
                if (! $item['exclude_discount'] && $this->discountData['order_discount'] && (float) $this->discountData['order_discount'] > 0) {
                    $this->orderData['discount'] = $this->orderItemsData[$key]['discount'] = $this->discountData['order_discount'];
                    $this->orderData['discount_type'] = $this->orderItemsData[$key]['discount_type'] = '%';
                    $this->orderItemsData[$key]['discount_price'] = (float) (($product_total - $current_sub) * $this->discountData['order_discount']) / 100;
                    $this->orderItemsData[$key]['discount_amount_wo_tax'] = (($product_total - $current_sub) * $this->discountData['order_discount']) / 100;
                    $this->orderItemsData[$key]['discount_inc_tax'] = (($product_total) * $this->discountData['order_discount']) / 100;
                    $this->orderData['discount_inc_tax'] += (($product_total) * $this->discountData['order_discount']) / 100;
                    $this->orderData['discount_amount'] += $this->orderItemsData[$key]['discount_price'];
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
            $this->orderItemsData[$key]['product_name'] = getUpdatedProductName($product);
            $this->orderItemsData[$key]['quantity'] = $item['quantity'];

            if ($this->system === SystemTypes::KIOSK) {
                $selected_supplements_data = $item['supplements'];
                $final_selected_supplements_data = [];
                companionLogger('Kiosk - selected supplement data', $selected_supplements_data);
                foreach ($selected_supplements_data as $select_supplement_data) {
                    $final_selected_supplements_data[$select_supplement_data['category_id']]['sub_cat_id'] = $select_supplement_data['category_id'];
                    $final_selected_supplements_data[$select_supplement_data['category_id']]['sub_cat_name'] = $select_supplement_data['category_name'];
                    $final_selected_supplements_data[$select_supplement_data['category_id']]['display_deselected'] = $select_supplement_data['deselect_supplement'] > 0;
                    $select_supplement = [];
                    $current_supplement = collect($this->supplementData)->where('id', $select_supplement_data['id'])->first();
                    $select_supplement['id'] = $select_supplement_data['id'];
                    $select_supplement['name'] = $select_supplement_data['name'];
                    $select_supplement['val'] = $select_supplement_data['val'];
                    $select_supplement['total_val'] = $select_supplement_data['total_val'];
                    $select_supplement['qty'] = $select_supplement_data['qty'];
                    $select_supplement['categoryId'] = isset($select_supplement_data['categoryId']) ? (int) $select_supplement_data['categoryId'] : null;
                    $select_supplement['alt_name'] = $current_supplement['alt_name'] ?? null;
                    $final_selected_supplements_data[$select_supplement_data['category_id']]['selected'][] = $select_supplement;
                }
                companionLogger('Kiosk - final selected supplement data', $final_selected_supplements_data);

                companionLogger('Kiosk - deselected supplement data', $item['deselect_sups']);
                $deselected_supplements_data = (isset($item['deselect_sups']) && ! empty($item['deselect_sups'])) ? $item['deselect_sups'] : [];
                foreach ($deselected_supplements_data as $deselect_supplement_data) {
                    $final_selected_supplements_data[$deselect_supplement_data['category_id']]['sub_cat_id'] = $deselect_supplement_data['category_id'];
                    $final_selected_supplements_data[$deselect_supplement_data['category_id']]['sub_cat_name'] = $deselect_supplement_data['category_name'];
                    $final_selected_supplements_data[$deselect_supplement_data['category_id']]['display_deselected'] = $deselect_supplement_data['deselect_supplement'] > 0;
                    $deselect_supplement = [];
                    $current_supplement = collect($this->supplementData)->where('id', $deselect_supplement_data['id'])->first();
                    $deselect_supplement['id'] = $deselect_supplement_data['id'];
                    $deselect_supplement['name'] = $deselect_supplement_data['name'];
                    $deselect_supplement['val'] = $deselect_supplement_data['val'];
                    $deselect_supplement['total_val'] = $deselect_supplement_data['val'];
                    $deselect_supplement['qty'] = 0;
                    $deselect_supplement['categoryId'] = isset($deselect_supplement_data['category_id']) ? (int) $deselect_supplement_data['category_id'] : null;
                    $deselect_supplement['alt_name'] = $current_supplement['alt_name'] ?? null;
                    $final_selected_supplements_data[$deselect_supplement_data['category_id']]['deselected'][] = $deselect_supplement;
                }

                companionLogger('Kiosk - final after deselect supplement data', $final_selected_supplements_data);

                $finalPreparedSupplementData = [];
                foreach ($final_selected_supplements_data as $supplementData) {
                    if (! isset($supplementData['selected'])) {
                        $supplementData['selected'] = [];
                    }
                    if (! isset($supplementData['deselected'])) {
                        $supplementData['deselected'] = [];
                    }
                    $finalPreparedSupplementData[] = $supplementData;
                }
                $item['supplements'] = $finalPreparedSupplementData;
            }

            $this->orderItemsData[$key]['extra'] = json_encode([
                'serve_type'  => $item['serve_type'] ?? [],
                'size'        => $item['size'] ?? [],
                'supplements' => $item['supplements'] ?? [],
                'weight'      => $item['weight'] ?? [],
                'users'       => $item['user_name'] ?? [],
                'transfer'    => $transferItems,
            ]);

            $this->orderItemsData[$key]['total_price'] = ($this->orderItemsData[$key]['sub_total'] - $this->orderItemsData[$key]['discount_price']) + (($this->orderItemsData[$key]['sub_total'] - $this->orderItemsData[$key]['discount_price']) * $this->orderItemsData[$key]['tax_percentage'] / 100);

            $isAddProductDeposite = true;
            if (! empty($this->storeReservation) || $this->system == SystemTypes::DINE_IN) {
                $isAddProductDeposite = false;
            }

            if ($normalOrder && $notVoided && $notOnTheHouse && $isAddProductDeposite) {
                $deposit = $product->statiege_id_deposite ?? 0;
                $this->orderItemsData[$key]['statiege_deposite_value'] = $deposit;
                $this->orderItemsData[$key]['total_price'] += $deposit * $item['quantity'];
                $this->orderItemsData[$key]['statiege_deposite_total'] = $deposit * $item['quantity'];
                $this->orderData['statiege_deposite_total'] += $deposit * $item['quantity'];
            } else {
                $this->orderItemsData[$key]['statiege_deposite_value'] = 0;
                $this->orderItemsData[$key]['statiege_deposite_total'] = 0;
                $this->orderData['statiege_deposite_total'] += 0;
            }
            if (isset($item['status'])) {
                $this->orderItemsData[$key]['status'] = $item['status'];
            } else {
                $this->orderItemsData[$key]['status'] = 'received';
            }
            if (($this->system == SystemTypes::POS || $this->system == SystemTypes::WAITRESS) && isset($this->payload['reservation_id']) && $this->payload['reservation_id']) {
                $this->orderItemsData[$key]['status'] = 'done';
            }
            $this->orderItemsData[$key]['comment'] = isset($item['comment']) && ! empty($item['comment']) ? $item['comment'] : null;
        }

        $this->orderData['sub_total'] = $this->orderData['normal_sub_total'] + $this->orderData['alcohol_sub_total'];
    }

    protected function calculateOrderDiscount()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (isset($this->discountData['order_discount']) && $this->discountData['order_discount'] > 0) {
                $this->orderData['discount_type'] = ($this->discountData['is_euro_discount'] == 1) ? 'EURO' : '%';
                $this->orderData['discount'] = $this->discountData['order_discount'];
                $this->orderData['discount_amount'] = discountCalc($this->orderData['total_price'], $this->orderData['sub_total'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']);
                $this->orderData['discount_inc_tax'] = discountCalc($this->orderData['total_price'], $this->orderData['total_price'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']);
                $this->orderData['total_tax'] = ($this->orderData['total_tax'] - discountCalc($this->orderData['total_price'], $this->orderData['total_tax'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']));
                $this->orderData['total_alcohol_tax'] = ($this->orderData['total_alcohol_tax'] - discountCalc($this->orderData['total_price'], $this->orderData['total_alcohol_tax'], $this->discountData['is_euro_discount'], $this->discountData['order_discount']));
            }
        }
        $this->orderData['total_price'] = $this->orderData['sub_total'] + $this->orderData['total_tax'] + $this->orderData['total_alcohol_tax'];
        $this->orderData['total_price'] -= $this->orderData['discount_amount'];
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
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if ($this->settings['additional_fee']['status'] == true) {
                $this->orderData['additional_fee'] = $this->settings['additional_fee']['value'];
                $this->orderData['total_price'] += $this->settings['additional_fee']['value'];
            }
        }
        if ($this->system == SystemTypes::TAKEAWAY) {
            if ($this->settings['delivery_fee']['status'] == true) {
                $this->orderData['delivery_fee'] = $this->settings['delivery_fee']['value'];
                $this->orderData['total_price'] += $this->settings['delivery_fee']['value'];
            }
            if ($this->settings['additional_fee']['status'] == true) {
                $this->orderData['additional_fee'] = $this->settings['additional_fee']['value'];
                $this->orderData['total_price'] += $this->settings['additional_fee']['value'];
            }
            if (isset($this->payload['is_pay_later_order']) && $this->payload['is_pay_later_order'] == 1) {
                $this->orderData['additional_fee'] = 0;
            }
            if ($this->settings['plastic_bag_fee']['status'] == true) {
                $this->orderData['plastic_bag_fee'] = $this->settings['plastic_bag_fee']['value'];
                $this->orderData['total_price'] += $this->settings['plastic_bag_fee']['value'];
            }
        }

        if ($this->system == SystemTypes::DINE_IN) {
            if ($this->settings['additional_fee']['status'] == true) {
                $this->orderData['additional_fee'] = $this->settings['additional_fee']['value'];
                $this->orderData['total_price'] += $this->settings['additional_fee']['value'];
            }

            if ($this->settings['plastic_bag_fee']['status'] == true) {
                $this->orderData['plastic_bag_fee'] = $this->settings['plastic_bag_fee']['value'];
                $this->orderData['total_price'] += $this->settings['plastic_bag_fee']['value'];
            }
        }

        if ($this->system == SystemTypes::KIOSK) {
            if ($this->settings['delivery_fee']['status'] == true) {
                $this->orderData['delivery_fee'] = $this->settings['delivery_fee']['value'];
                $this->orderData['total_price'] += $this->settings['delivery_fee']['value'];
            }
            if ($this->settings['plastic_bag_fee']['status'] == true) {
                $this->orderData['plastic_bag_fee'] = $this->settings['plastic_bag_fee']['value'];
                $this->orderData['total_price'] += $this->settings['plastic_bag_fee']['value'];
            }
        }
    }

    protected function calculateStatiegeDeposite()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            $normalOrder = (isset($this->payload['reservation_id']) && $this->payload['reservation_id'] != '' && ! empty($this->storeReservation)) ? 0 : 1;
            if ($normalOrder) {
                $this->orderData['total_price'] += $this->orderData['statiege_deposite_total'];
            }
        }

        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->orderData['total_price'] += $this->orderData['statiege_deposite_total'];
        }

        if ($this->system == SystemTypes::KIOSK) {
            $this->orderData['total_price'] += $this->orderData['statiege_deposite_total'];
        }
    }

    protected function calculateTipAmount()
    {
        if ($this->system == SystemTypes::POS && ! $this->isSubOrder) {
            $this->orderData['total_price'] = $this->orderData['tip_amount'];
        }
    }

    protected function calculateOriginOrderTotal()
    {
        $this->orderData['original_order_total'] = $this->orderData['total_price'] + $this->orderData['discount_inc_tax'];
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
