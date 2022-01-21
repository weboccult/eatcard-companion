<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Generators;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\DevicePrinter;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;
use function Weboccult\EatcardCompanion\Helpers\discountCalc;
use function Weboccult\EatcardCompanion\Helpers\set_discount_with_prifix;

class RunningOrderGenerator extends BaseGenerator
{
    protected string $orderType = OrderTypes::RUNNING;

    protected array $proformaProducts = [];

    //proforma calculator variables
    protected float $order_discount = 0;
    protected float $is_euro_discount_order = 0;

    protected string $order_discount_amount_with_prefix = '';

    protected float $alcohol_sub_total = 0;
    protected float $total_alcohol_tax = 0;
    protected float $total_dis_inc_tax = 0;
    protected float $total_dis_wo_tax = 0;
    protected float $total_alcohol_tax_with_dis = 0;
    protected float $alcohol_sub_total_with_dis = 0;

    protected float $normal_sub_total = 0;
    protected float $total_tax = 0;
    protected float $total_tax_with_dis = 0;
    protected float $normal_sub_total_with_dis = 0;

    protected float $sub_total = 0;
    protected float $total_price = 0;

    protected float $product_total = 0;
    protected float $is_euro_discount = 0;
    protected float $item_discount = 0;
    protected int $isVoidProduct = 0;
    protected int $isOnTheHouseProduct = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     * prepare Running order items details for print in Proforma and Kitchen print.
     * skip for ,Paid, save and Sub order
     * Skip for Until Kitchen Print
     * here we will modify item name base on product settings,
     * modify item name with add void, on-the-house, discount 's postfix.
     * set Kitchen and Label printer for print it based on there print type and settings.
     */
    protected function prepareRunningOrderItems()
    {
        if ($this->printType != PrintTypes::PROFORMA && $this->additionalSettings['is_until']) {
            // for until reservation, we need to exclude the kitchen print : EAT-1967
            return;
        }

        $isSingleRound = false;
        $reservationOrderItems = [];
        if (! empty($this->reservationOrderItems)) {
            $isSingleRound = true;
            $reservationOrderItems[] = $this->reservationOrderItems;
        }
        if (empty($this->reservation)) {
            throw new StoreReservationEmptyException();
        }

        if ($this->printType == PrintTypes::PROFORMA) {
            $reservationOrderItems = ReservationOrderItem::query()->where('reservation_id', $this->reservationId)->get();
        }

        $items = [];
        foreach ($reservationOrderItems as $rk => $reservationOrderItem) {
            $cart = json_decode($reservationOrderItem['cart'], true);

            //print only if cart id set for kds kitchen print
            if (! empty($this->reservationOrderItemCartId)) {
                $cart = collect($cart)->where('cartId', $this->reservationOrderItemCartId);
            }

            foreach ($cart as $itemKey => $item) {

                /*<--- if IsTransfer = 1 then skip for print json ---->*/
                if (isset($item['isTransfer']) && $item['isTransfer'] == 1) {
                    continue;
                }
                //combine cart items for proforma print
                if (! $isSingleRound) {
                    $foundSame = false;
                    foreach ($items as $proKey => $proItem) {
                        $void_id = 1;
                        if (isset($item['void_id']) && isset($proItem['void_id'])) {
                            if ($item['void_id'] == $proItem['void_id']) {
                                $void_id = 1;
                            } else {
                                $void_id = 0;
                            }
                        }
                        //combine products
                        if ($void_id == 1 && ((isset($item['on_the_house']) && isset($proItem['on_the_house']) && $item['on_the_house'] == $proItem['on_the_house']) || (! isset($item['on_the_house']) || ! isset($proItem['on_the_house']))) && $item['id'] == $proItem['id'] && $item['is_per_weight'] != 1 && json_encode($item['size']) == json_encode($proItem['size']) && json_encode($item['supplements']) == json_encode($proItem['supplements']) && $item['serve_type'] == $proItem['serve_type'] && $item['comment'] == $proItem['comment']) {
                            $foundSame = true;
                            $items[$proKey]['quantity'] = $items[$proKey]['quantity'] + $item['quantity'];
                        }
                    }
                    if ($foundSame == false) {
                        $items[] = $item;
                    }
                } else {
                    $items[] = $item;
                }
            }
        }

        $this->proformaProducts = $items;
        //set category
        $this->setCategoryData();
        //sort order items as per categories sequence
        $sortedItems = [];
        if (! empty($this->categories)) {
            foreach ($this->categories as $category) {
                foreach ($items as $order_key => $order_item) {
                    if ($category->id == $order_item['category']['id']) {
                        $sortedItems[] = $order_item;
                        unset($items[$order_key]);
                    }
                }
            }
        }

        $items = $sortedItems;
        $product_ids = [];
        $categories = [];
        $category_settings = [];
        if (! empty($items)) {
            $product_ids = collect($items)->pluck('id')->toArray();
        }
        $products = Product::withTrashed()->with([
            'category',
            'printers',
        ])->whereIn('id', $product_ids)->get()->keyBy('id');

        if (! empty($this->reservation) && isset($this->reservation->discount_type) && isset($this->reservation->discount) && (float) $this->reservation->discount > 0) {
            $this->is_euro_discount_order = $this->reservation->discount_type == 'EURO' ? 1 : 0;
            $this->order_discount = (float) $this->reservation->discount;
            $this->order_discount_amount_with_prefix = set_discount_with_prifix($this->is_euro_discount_order, $this->order_discount);
        }

        foreach ($items as $itemKey => $item) {
            //clone item format
            $newItem = $this->itemFormat;
            $item['product'] = $products[$item['id']] ?? [];
            $newItem['qty'] = ''.$item['quantity'];

            //show pcs if product al a carte setting is on and order is cart order
            $isProductAlACarte = $item['product']['is_al_a_carte'] ?? false;
            $productTotalPcs = (int) ($item['product']['total_pieces'] ?? 0);
            $productPostFix = '';
            if ($this->additionalSettings['show_no_of_pieces'] && $isProductAlACarte) {
                $productPostFix = ' | '.$productTotalPcs.($productTotalPcs > 0 ? 'pcs' : 'pc');
            }
            //set product name
            $newItem['itemname'] = ($item['product']['sku'] ? $item['product']['sku'].'.' : '').$item['product']['name'].$productPostFix;

            if (isset($item['product']['category']['id'])) {
                if (! in_array($item['product']['category']['id'], $categories)) {
                    //category settings
                    $category_settings[] = [
                        'id' => strval($item['product']['category']['id']),
                        'by_category' => $item['product']['category']['kitchen_print_per_category'] == 1 ? true : false,
                        'by_product' => $item['product']['category']['kitchen_print_per_product'] == 1 ? true : false,
                    ];
                }
                $categories[] = $item['product']['category']['id'];
            }
            //set product category
            $newItem['category'] = ''.($item['product']['category_id'] ?? '');
            // set product kitchen descriptopn
            $newItem['kitchendescription'] = $item['product']['alt_name'] ?? '';

            //calculate product total price
            $item_total = 0;

            if ($item['base_price']) {
                $item['unit_price'] = $item['product']['price'];
            } else {
                $item['unit_price'] = 0;
            }

            if ($item['serve_type']) {
                $newItem['itemaddons'][] = $item['serve_type'];
                $newItem['kitchenitemaddons'][] = $item['serve_type'];
            }
            if ($item['weight']) {
                $newItem['itemaddons'][] = $item['weight'].'g';
                $newItem['kitchenitemaddons'][] = $item['weight'].'g';
                if ($item['base_price']) {
                    $item['unit_price'] = ((int) $item['weight'] * ($item['product']['price'])) / $item['actual_weight'];
                }
            }
            if ($item['size']) {
                $size_price = isset($item['size']['price']) ? (float) $item['size']['price'] : 0;
                $item_total += $size_price;
                $current = __('eatcard-companion::print.'.$item['size']['name']);
                $newItem['itemaddons'][] = $current;
                $newItem['kitchenitemaddons'][] = $current;
            }
            if ($item['supplements']) {
                foreach ($item['supplements'] as $sup) {
                    $sup_price = isset($sup['val']) ? (float) $sup['val'] : 0;
                    $current = '+ '.$sup['name'].(($sup['qty'] && $sup['qty'] > 1) ? ' ('.$sup['qty'].'x)' : '');
                    $newItem['itemaddons'][] = $current;
                    $newItem['kitchenitemaddons'][] = $current;
                    if ($this->additionalSettings['show_supplement_kitchen_name'] && isset($sup['id'])) {
                        $supplement_detail = Supplement::withTrashed()->where('id', $sup['id'])->first();
                        if (! empty($supplement_detail) && $supplement_detail->alt_name != null && $supplement_detail->alt_name != '') {
                            $newItem['kitchenitemaddons'][] = '  '.$supplement_detail->alt_name;
                        }
                    }
                    $item_total += $sup_price * isset($sup['qty']) ? $sup['qty'] : 1;
                }
            }

            $newItem['mainproductcomment'] = '';
            if (isset($item['comment']) && $item['comment'] != null && $item['comment'] != '') {
                $newItem['mainproductcomment'] = $this->additionalSettings['show_product_comment_in_main_receipt'] == 1 ? $item['comment'] : '';
                $newItem['comment'] = $item['comment'];
            }

            if (! $isSingleRound) {
                $this->itemPricesCalculate($item);

                if ($this->order_discount == 0 && $this->item_discount > 0 && $this->isVoidProduct == false && $this->isOnTheHouseProduct == false) {
                    $newItem['itemname'] .= set_discount_with_prifix($this->is_euro_discount, $this->item_discount);
                }

                if ($this->isVoidProduct) {
                    $newItem['void_id'] = 1;
                    $newItem['itemname'] .= ' - void';
                }

                if ($this->isOnTheHouseProduct) {
                    $newItem['on_the_house'] = 1;
                    $newItem['itemname'] .= ' - on the house';
                }
            }

            $newItem['price'] = ''.changePriceFormat($this->product_total);
            $newItem['original_price'] = $this->product_total;

            if ((isset($item['on_the_house']) && $item['on_the_house'] == 1) || (isset($item['void_id']) && $item['void_id'] > 0)) {
                $newItem['price'] = '0';
                $newItem['original_price'] = 0;
            }

            /*set kitchen and label printer for each product*/
            $kds_Kitchen = [];
            $pro_kitchen = [];
            $kitchen = [];
            $label = [];
            $device_id = 0;
            $printer_type = '';
            $isPrintAddon = false;
            $skipKitchenLabelPrint = false;

            //if kds user set then overwrite all settings
            if (! empty($this->kdsUser)) {
                $kds_Kitchen = [($this->kdsUser->printer_name ?? '')];
                //set final kitchen and label printer
                $newItem['printername'] = $kds_Kitchen;
                $newItem['labelprintname'] = [];
                $skipKitchenLabelPrint = true;
            }

            // no need to print if order is already saved
            if ($this->skipKitchenLabelPrint || $skipKitchenLabelPrint) {
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::DEFAULT && $this->additionalSettings['is_print_cart_add'] == 1 && in_array($item['product']['category_id'], $this->additionalSettings['addon_print_categories'])) {
                // no need to print if printed already
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::PROFORMA) {
                $skipKitchenLabelPrint = true;
            }

            if ($skipKitchenLabelPrint == false) {
                $printer = Cache::tags([
                    FLUSH_ALL,
                    FLUSH_POS,
                    FLUSH_STORE_BY_ID.$this->store->id,
                    DEVICE_PRINTERS.$this->store->id,
                ])
                    ->remember('{eat-card}-device-printers-'.$item['product']['category']['printer_id'], CACHING_TIME, function () use ($item) {
                        return DevicePrinter::query()->where('id', $item['product']['category']['printer_id'])->first();
                    });
                $device_id = $item['product']['category']['device_id'];
                $printer_type = $item['product']['category']['printer_type'];
                if ($item['product']['printers']) {
                    $pro_kitchen = collect($item['product']['printers'])->pluck('printer_name')->toArray();
                }
                if ($printer_type == 'kitchen' && $printer) {
                    $kitchen = [$printer->name];
                    $label = [];
                } elseif ($printer_type == 'label' && $printer) {
                    $kitchen = [];
                    $label = [$printer->name];
                } elseif ($printer_type == 'all_printer') {
                    $kitchen_printer = Cache::tags([
                        FLUSH_ALL,
                        FLUSH_POS,
                        FLUSH_STORE_BY_ID.$this->store->id,
                        DEVICE_PRINTERS.$this->store->id,
                    ])
                        ->remember('{eat-card}-device-printer-kitchen-'.$device_id, CACHING_TIME, function () use ($item, $device_id) {
                            return DevicePrinter::query()
                                ->where('store_device_id', $device_id)
                                ->where('printer_type', 'kitchen')
                                ->get();
                        });
                    $label_printer = Cache::tags([
                        FLUSH_ALL,
                        FLUSH_POS,
                        FLUSH_STORE_BY_ID.$this->store->id,
                        DEVICE_PRINTERS.$this->store->id,
                    ])
                        ->remember('{eat-card}-device-printer-label-'.$device_id, CACHING_TIME, function () use ($item, $device_id) {
                            return DevicePrinter::query()
                                ->where('store_device_id', $device_id)
                                ->where('printer_type', 'label')
                                ->get();
                        });
                    $kitchen = $kitchen_printer ? $kitchen_printer->pluck('name')->toArray() : [];
                    $label = $label_printer ? $label_printer->pluck('name')->toArray() : [];
                }

                $newItem['printername'] = ! empty($pro_kitchen) ? $pro_kitchen : (! empty($kitchen) ? $kitchen : []);
                $newItem['labelprintname'] = ! empty($label) ? $label : [];
            }

            //assign item to global items and increase index
            $this->jsonItems[$this->jsonItemsIndex] = $newItem;
            $this->jsonItemsIndex += 1;
        }

        if (! $isSingleRound) {
            $this->sub_total = $this->normal_sub_total + $this->alcohol_sub_total;
            $this->total_price = $this->sub_total + $this->total_tax + $this->total_alcohol_tax;
            // is item discount is set than calculate it.
            if ($this->order_discount > 0) {
                $this->total_dis_inc_tax = discountCalc($this->total_price, $this->total_price, $this->is_euro_discount_order, $this->order_discount);
                $this->total_dis_wo_tax = discountCalc($this->total_price, $this->sub_total, $this->is_euro_discount_order, $this->order_discount);
                $this->normal_sub_total = $this->normal_sub_total - discountCalc($this->total_price, $this->normal_sub_total, $this->is_euro_discount_order, $this->order_discount);
                $this->alcohol_sub_total = $this->alcohol_sub_total - discountCalc($this->total_price, $this->alcohol_sub_total, $this->is_euro_discount_order, $this->order_discount);
                $this->total_tax = $this->total_tax - discountCalc($this->total_price, $this->total_tax, $this->is_euro_discount_order, $this->order_discount);
                $this->total_alcohol_tax = $this->total_alcohol_tax - discountCalc($this->total_price, $this->total_alcohol_tax, $this->is_euro_discount_order, $this->order_discount);
            }
            //if item wise discount is set then need to update tax amount as per discount
            if ($this->order_discount == 0 && $this->total_dis_inc_tax > 0) {
                $this->total_tax = $this->total_tax - $this->total_tax_with_dis;
                $this->total_alcohol_tax = $this->total_alcohol_tax - $this->total_alcohol_tax_with_dis;
                $this->alcohol_sub_total = $this->alcohol_sub_total - $this->alcohol_sub_total_with_dis;
                $this->normal_sub_total = $this->normal_sub_total - $this->normal_sub_total_with_dis;
            }
            $this->total_price = $this->normal_sub_total + $this->alcohol_sub_total + $this->total_tax + $this->total_alcohol_tax;
        }

        $this->additionalSettings['categories_settings'] = $category_settings;
    }

    /**
     * @param $item
     * @param $item_total
     *
     * @return void
     * calculate product tax, discount, product deposit and other things.
     */
    protected function itemPricesCalculate($item, $isAYCEProduct = false)
    {
        $this->is_euro_discount = 0;
        $this->item_discount = 0;
        $this->product_total = 0;
        $this->isVoidProduct = false;
        $this->isOnTheHouseProduct = false;

        if ($this->order_discount == 0 && isset($item['is_euro_discount']) && isset($item['discount']) && (float) $item['discount'] > 0) {
            $this->is_euro_discount = $item['is_euro_discount'];
            $this->item_discount = (float) $item['discount'];
        }

        $notVoided = ! (isset($item['void_id']) && $item['void_id'] != '');
        $this->isVoidProduct = ! $notVoided;
        $notOnTheHouse = ! (isset($item['on_the_house']) && $item['on_the_house'] == '1');
        $this->isOnTheHouseProduct = ! $notOnTheHouse;

        if ($isAYCEProduct) {
            $product_tax = 9;
            $product_total = (float) ($item['original_price'] ?? 0);
        } else {
            $product_tax = isset($item['product']['tax']) && $item['product']['tax'] != null ? $item['product']['tax'] : $item['product']['category']['tax'];
            if (isset($item['weight']) && $item['weight'] && $item['actual_weight']) {
                $product_total = ($item['base_price'] + @$item['tax_amount'] + @$item['alcohol_tax_amount']) * $item['weight'];
            } else {
                $product_total = ($item['base_price'] + @$item['tax_amount'] + @$item['alcohol_tax_amount']) * $item['quantity'];
            }
        }

        $this->product_total = $product_total;

        if ($product_tax == 21) {
            $current_sub = ($product_total * $product_tax / 121);
            if ($notVoided && $notOnTheHouse) {
                $this->alcohol_sub_total += $product_total - $current_sub;
                $this->total_alcohol_tax += $current_sub;
                // is item discount is set than calculate it.
                if ($this->order_discount == 0 && $this->item_discount > 0) {
                    $this->total_dis_inc_tax += discountCalc($product_total, $product_total, $this->is_euro_discount, $this->item_discount);
                    $this->total_dis_wo_tax += discountCalc($product_total, ($product_total - $current_sub), $this->is_euro_discount, $this->item_discount);
                    $this->total_alcohol_tax_with_dis += discountCalc($product_total, $current_sub, $this->is_euro_discount, $this->item_discount);
                    $this->alcohol_sub_total_with_dis += discountCalc($product_total, $product_total - $current_sub, $this->is_euro_discount, $this->item_discount);
                }
            }
        } else {
            $current_sub = ($product_total * $product_tax / 109);
            if ($notVoided && $notOnTheHouse) {
                $this->normal_sub_total += $product_total - $current_sub;
                $this->total_tax += $current_sub;
                // is item discount is set than calculate it.
                if ($this->order_discount == 0 && $this->item_discount > 0) {
                    $this->total_dis_inc_tax += discountCalc($product_total, $product_total, $this->is_euro_discount, $this->item_discount);
                    $this->total_dis_wo_tax += discountCalc($product_total, ($product_total - $current_sub), $this->is_euro_discount, $this->item_discount);
                    $this->total_tax_with_dis += discountCalc($product_total, $current_sub, $this->is_euro_discount, $this->item_discount);
                    $this->normal_sub_total_with_dis += discountCalc($product_total, ($product_total - $current_sub), $this->is_euro_discount, $this->item_discount);
                }
            }
        }
    }

    /**
     * @return void
     * Prepare summary based or related order data
     */
    protected function prepareSummary()
    {
        if ($this->skipMainPrint) {
            return;
        }

        $summary = [];

        if ($this->sub_total > 0) {
            $summary[] = [
                   'key'   => __('eatcard-companion::general.sub_total'),
                   'value' => ''.changePriceFormat($this->sub_total),
               ];
        }
//           if ($this->statiege_deposite_total > 0) {
//               $summary[] = [
//                   'key'   => 'Deposit',
//                   'value' => ''.changePriceFormat($this->statiege_deposite_total),
//               ];
//           }
        if ($this->total_dis_wo_tax > 0) {
            $summary[] = [
                   'key'   => __('eatcard-companion::general.discount_amount').$this->order_discount_amount_with_prefix,
                   'value' => ''.changePriceFormat($this->total_dis_wo_tax),
               ];
        }
        if ($this->total_tax > 0) {
            $summary[] = [
                   'key'   => 'BTW laag',
                   'value' => ''.changePriceFormat($this->total_tax),
               ];
        }
        if ($this->total_alcohol_tax > 0) {
            $summary[] = [
                   'key'   => 'BTW hoog',
                   'value' => ''.changePriceFormat($this->total_alcohol_tax),
               ];
        }
//           if ($delivery_fee > 0) {
//               $summary[] = [
//                   'key'   => 'Bezorgkosten',
//                   'value' => ''.changePriceFormat($delivery_fee),
//               ];
//           }
//           if ($additional_fee > 0) {
//               $summary[] = [
//                   'key'   => __('eatcard-companion::general.additional_fees'),
//                   'value' => ''.changePriceFormat($additional_fee),
//               ];
//           }
//           if ($plastic_bag_fee > 0) {
//               $summary[] = [
//                   'key'   => __('eatcard-companion::general.bag'),
//                   'value' => ''.changePriceFormat($plastic_bag_fee),
//               ];
//           }
//           if ($coupon_price > 0) {
//               $summary[] = [
//                   'key'   => __('eatcard-companion::general.gift_voucher_cost'),
//                   'value' => ''.changePriceFormat($coupon_price),
//               ];
//           }
//           if ($cash_paid > 0 && $method == 'cash') {
//               $summary[] = [
//                   'key'   => __('eatcard-companion::general.cash_paid_cost'),
//                   'value' => ''.changePriceFormat($cash_paid),
//               ];
//           }
//           $cash_changes = $cash_paid - $total_price;
//           if ($cash_paid > 0 && $cash_changes > 0 && $method == 'cash') {
//               $summary[] = [
//                   'key'   => __('eatcard-companion::general.cash_changes'),
//                   'value' => ''.changePriceFormat($cash_changes),
//               ];
//           }
        if (($this->reservation['reservation_paid'] ?? 0) > 0) {
            $summary[] = [
                   'key'   => 'Booking deposit',
                   'value' => ''.changePriceFormat(($this->reservation['reservation_paid'] ?? 0)),
               ];
        }

        $this->jsonSummary = $summary;
    }
}
