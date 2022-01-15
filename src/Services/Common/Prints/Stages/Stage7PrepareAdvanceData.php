<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\DevicePrinter;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\set_discount_with_prifix;

/**
 * @description Stag 7
 * @mixin BaseGenerator
 */
trait Stage7PrepareAdvanceData
{
    protected function prepareTableName()
    {
        $tableName = '';

        if ($this->orderType == OrderTypes::PAID) {
            $tableName = $this->order['table_name'] ?? '';
        }

        if (in_array($this->orderType, [OrderTypes::PAID, OrderTypes::RUNNING]) && ! empty($this->reservation)) {
            if (isset($this->reservation['tables2']) && $this->reservation['tables2']->count() > 0) {
                $tables = $this->reservation['tables2']->pluck('name')->toArray();
                $tableName = implode(',', $tables);
            }

            if ($this->additionalSettings['dinein_guest_order'] && isset($this->order['table_name']) && ! empty($this->order['table_name'])) {
                $tableName = ($this->order['table_name']) ? __('messages.table_name').' '.$this->order['table_name'] : '';
            }
        }

        $this->advanceData['tableName'] = $tableName;
    }

    protected function prepareDynamicOrderNo()
    {
        $dynamicOrderNo = '';
        if ($this->orderType == OrderTypes::PAID) {
            if (! empty($this->additionalSettings['print_dynamic_order_no'])) {
                $dynamicOrderNo = ''.substr(($this->order['order_id'] ?? ''), -3);
            } else {
                $dynamicOrderNo = ''.substr(($this->order['order_id'] ?? ''), -2);
            }
        }

        $this->advanceData['dynamicOrderNo'] = trim($dynamicOrderNo);
    }

    protected function processOrderData()
    {
        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['paid_on'])) {
                $this->order['paid_on'] = Carbon::parse($this->order['paid_on'])->format('d-m-Y H:i');
            }

            if (isset($this->order['order_date'])) {
                $this->order['order_date'] = Carbon::parse($this->order['order_date'])->format('d-m-Y');
            }

//            if (isset($this->order['ccv_customer_receipt'])) {
//                $this->order['ccv_customer_receipt'] = json_decode($this->order['ccv_customer_receipt'], true);
//            }

            //update address postcode
            $delivery_address = $this->order['delivery_address'] ?? '';
            $delivery_postcode = $this->order['delivery_postcode'] ?? '';
            if (! empty($delivery_address)) {
                $delivery_address = explode(',', (substr($delivery_address, 0, strrpos($delivery_address, ','))));
                if (isset($delivery_address[1]) && $delivery_postcode) {
                    $delivery_address[1] = $delivery_postcode.','.$delivery_address[1];
                }
                $this->order['delivery_address'] = implode(', ', $delivery_address);
            }

            //update order type for Dine-in store-qr orders
            if (empty($this->reservation) && isset($this->order['order_type']) && $this->order['order_type'] == 'dine_in') {
                $this->order['order_type'] = 'qr_code_type';
            }
        }
    }

    protected function setThirdPartyName()
    {
        $thirdPartyName = '';

        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['thusibezorgd_order_id']) && empty($this->order['thusibezorgd_order_id'])) {
                $thirdPartyName = 'Thuisbezorgd: ';
            } elseif (isset($this->order['uber_eats_order_id']) && empty($this->order['uber_eats_order_id'])) {
                $thirdPartyName = 'Uber: ';
            } elseif (isset($this->order['deliveroo_order_id']) && empty($this->order['deliveroo_order_id'])) {
                $thirdPartyName = 'Deliveroo: ';
            }
        }

        $this->additionalSettings['thirdPartyName'] = $thirdPartyName;
    }

    public function prepareFullReceiptFlag()
    {
        if ($this->skipMainPrint) {
            return;
        }

        $fullReceipt = '1';
        $excludeSystemName = '';

        if ($this->systemType == SystemTypes::POS) {
            $excludeSystemName = $this->additionalSettings['exclude_print_status'] ? 'pos' : '';
        } elseif ($this->systemType == SystemTypes::DINE_IN) {
            $excludeSystemName = 'dine_in';
        } elseif ($this->systemType == SystemTypes::TAKEAWAY) {
            $excludeSystemName = 'takeaway';
        }

        $excludePrintSystem = explode(',', $this->additionalSettings['exclude_print_from_main_print']);
        if (! empty($excludePrintSystem) && in_array($excludeSystemName, $excludePrintSystem)) {
            $fullReceipt = '0';
        }

        $this->additionalSettings['fullreceipt'] = $fullReceipt;
    }

    public function prepareAYCEItems()
    {
        // no need to prepare ayce item if main print will not print
        if ($this->skipMainPrint) {
            return;
        }

        $all_you_eat_data = [];
        //return if no ayce data found
        if (empty($this->additionalSettings['ayce_data'])) {
            return;
        }

        $all_you_eat_data = $this->additionalSettings['ayce_data'];

        //if dinein price class not set or blank then return it
        if (! (isset($all_you_eat_data['dinein_price']) && ! empty($all_you_eat_data['dinein_price']))) {
            return;
        }

        if (isset($all_you_eat_data['no_of_adults']) && ! empty($all_you_eat_data['no_of_adults'])) {
            $item = $this->itemFormat;

            $item['qty'] = $all_you_eat_data['no_of_adults'];
            $item['itemname'] = $all_you_eat_data['dinein_price']['name'] ?? '';
            $item['printername'] = [];
            $item['labelprintname'] = [];
            $item['price'] = ''.changePriceFormat(@$all_you_eat_data['dinein_price']['price'] * @$all_you_eat_data['no_of_adults']);
            $item['original_price'] = @$all_you_eat_data['dinein_price']['price'] * @$all_you_eat_data['no_of_adults'];
            $item['kitchendescription'] = '';
            $item['mainproductcomment'] = '';
            $item['itemaddons'] = [];
            $item['kitchenitemaddons'] = [];
            $item['category'] = '';
            $item['on_the_house'] = 0;
            $item['void_id'] = 0;

            $this->jsonItems[$this->jsonItemsIndex] = $item;
            $this->jsonItemsIndex += 1;
        }

        if (isset($all_you_eat_data['no_of_kids2']) && ! empty($all_you_eat_data['no_of_kids2'])) {
            $item = $this->itemFormat;
            $item['qty'] = $all_you_eat_data['no_of_kids2'];
            $item['itemname'] = $all_you_eat_data['dinein_price']['child_name_2'] ?? '';
            $item['printername'] = [];
            $item['labelprintname'] = [];
            $item['price'] = ''.changePriceFormat(@$all_you_eat_data['dinein_price']['child_price_2'] * @$all_you_eat_data['no_of_kids2']);
            $item['original_price'] = @$all_you_eat_data['dinein_price']['child_price_2'] * @$all_you_eat_data['no_of_adults'];
            $item['kitchendescription'] = '';
            $item['mainproductcomment'] = '';
            $item['itemaddons'] = [];
            $item['kitchenitemaddons'] = [];
            $item['category'] = '';
            $item['on_the_house'] = 0;
            $item['void_id'] = 0;

            $this->jsonItems[$this->jsonItemsIndex] = $item;
            $this->jsonItemsIndex += 1;
        }

        if (isset($all_you_eat_data['no_of_kids']) && $all_you_eat_data['no_of_kids']) {
            if ($all_you_eat_data['dinein_price']['is_per_year']) {
                if (isset($all_you_eat_data['kids_age']) && ! empty($all_you_eat_data['kids_age'])) {
                    foreach ($all_you_eat_data['kids_age'] as $kid) {
                        $item = $this->itemFormat;
                        $item['qty'] = 1;
                        $item['itemname'] = $all_you_eat_data['dinein_price']['child_name'].' '.$kid.' years';
                        $item['printername'] = [];
                        $item['labelprintname'] = [];
                        $item['kitchendescription'] = '';
                        $item['mainproductcomment'] = '';
                        $item['itemaddons'] = [];
                        $item['kitchenitemaddons'] = [];
                        $item['category'] = '';
                        $item['original_price'] = 0;
                        $item['on_the_house'] = 0;
                        $item['void_id'] = 0;
                        if ($all_you_eat_data['dinein_price']['min_age']) {
                            $min_price = @$all_you_eat_data['dinein_price']['child_price'];
                            $item['price'] = ''.changePriceFormat(@$all_you_eat_data['dinein_price']['child_price'] * ($kid - $all_you_eat_data['dinein_price']['min_age'] + 1));
                            $item['original_price'] = @$all_you_eat_data['dinein_price']['child_price'] * ($kid - $all_you_eat_data['dinein_price']['min_age'] + 1);
                        }

                        $this->jsonItems[$this->jsonItemsIndex] = $item;
                        $this->jsonItemsIndex += 1;
                    }
                }
            } else {
                $item = $this->itemFormat;
                $item['qty'] = $all_you_eat_data['no_of_kids'];
                $item['itemname'] = $all_you_eat_data['dinein_price']['child_name'];
                $item['printername'] = [];
                $item['labelprintname'] = [];
                $item['kitchendescription'] = '';
                $item['mainproductcomment'] = '';
                $item['itemaddons'] = [];
                $item['kitchenitemaddons'] = [];
                $item['category'] = '';
                $item['on_the_house'] = 0;
                $item['void_id'] = 0;
                $item['price'] = ''.changePriceFormat(@$all_you_eat_data['dinein_price']['child_price'] * @$all_you_eat_data['no_of_kids']);
                $item['original_price'] = @$all_you_eat_data['dinein_price']['child_price'] * @$all_you_eat_data['no_of_kids'];

                $this->jsonItems[$this->jsonItemsIndex] = $item;
                $this->jsonItemsIndex += 1;
            }
        }

        if (isset($all_you_eat_data['dinein_price']['dynamic_prices']) && ! empty($all_you_eat_data['dinein_price']['dynamic_prices'])) {
            foreach ($all_you_eat_data['dinein_price']['dynamic_prices'] as $dynamic_price_person) {
                if (isset($dynamic_price_person['person']) && (int) $dynamic_price_person['person'] > 0) {
                    $item = $this->itemFormat;
                    $item['qty'] = $dynamic_price_person['person'];
                    $item['itemname'] = $dynamic_price_person['name'];
                    $item['printername'] = [];
                    $item['labelprintname'] = [];
                    $item['price'] = ''.changePriceFormat(@(float) $dynamic_price_person['price'] * @(int) $dynamic_price_person['person']);
                    $item['original_price'] = @(float) $dynamic_price_person['price'] * @(int) $dynamic_price_person['person'];
                    $item['kitchendescription'] = '';
                    $item['mainproductcomment'] = '';
                    $item['itemaddons'] = [];
                    $item['kitchenitemaddons'] = [];
                    $item['category'] = '';
                    $item['on_the_house'] = 0;
                    $item['void_id'] = 0;

                    $this->jsonItems[$this->jsonItemsIndex] = $item;
                    $this->jsonItemsIndex += 1;
                }
            }
        }

        if (isset($all_you_eat_data['house']) && $all_you_eat_data['house']) {
            if (isset($all_you_eat_data['adult']) && $all_you_eat_data['adult']) {
                $item = $this->itemFormat;
                $item['qty'] = @$all_you_eat_data['adult'];
                $item['itemname'] = @$all_you_eat_data['dinein_price']['name'].' - On the house';
                $item['printername'] = [];
                $item['labelprintname'] = [];
                $item['price'] = '0,00';
                $item['original_price'] = 0;
                $item['kitchendescription'] = '';
                $item['mainproductcomment'] = '';
                $item['itemaddons'] = [];
                $item['kitchenitemaddons'] = [];
                $item['category'] = '';
                $item['on_the_house'] = 1;
                $item['void_id'] = 0;

                $this->jsonItems[$this->jsonItemsIndex] = $item;
                $this->jsonItemsIndex += 1;
            }
            if (isset($all_you_eat_data['kid2']) && $all_you_eat_data['kid2']) {
                $item = $this->itemFormat;
                $item['qty'] = $all_you_eat_data['kid2'];
                $item['itemname'] = $all_you_eat_data['dinein_price']['child_name_2'].' - On the house';
                $item['printername'] = [];
                $item['labelprintname'] = [];
                $item['price'] = '0,00';
                $item['original_price'] = 0;
                $item['kitchendescription'] = '';
                $item['mainproductcomment'] = '';
                $item['itemaddons'] = [];
                $item['kitchenitemaddons'] = [];
                $item['category'] = '';
                $item['on_the_house'] = 1;
                $item['void_id'] = 0;

                $this->jsonItems[$this->jsonItemsIndex] = $item;
                $this->jsonItemsIndex += 1;
            }
            if (isset($all_you_eat_data['kid1']) && $all_you_eat_data['kid1']) {
                if ($all_you_eat_data['dinein_price']['is_per_year']) {
                    if (isset($all_you_eat_data['on_the_house_kids_age']) && ! empty($all_you_eat_data['on_the_house_kids_age'])) {
                        foreach ($all_you_eat_data['on_the_house_kids_age'] as $kid) {
                            $item = $this->itemFormat;
                            $item['qty'] = 1;
                            $item['itemname'] = $all_you_eat_data['dinein_price']['child_name'].' '.$kid.' years - On the house';
                            $item['printername'] = [];
                            $item['labelprintname'] = [];
                            $item['kitchendescription'] = '';
                            $item['mainproductcomment'] = '';
                            $item['itemaddons'] = [];
                            $item['kitchenitemaddons'] = [];
                            $item['category'] = '';
                            $item['original_price'] = 0;
                            $item['on_the_house'] = 1;
                            $item['void_id'] = 0;
                            if ($all_you_eat_data['dinein_price']['min_age']) {
                                $item['price'] = '0,00';
                                $item['original_price'] = 0;
                            }

                            $this->jsonItems[$this->jsonItemsIndex] = $item;
                            $this->jsonItemsIndex += 1;
                        }
                    }
                } else {
                    $item = $this->itemFormat;
                    $item['qty'] = $all_you_eat_data['kid1'];
                    $item['itemname'] = $all_you_eat_data['dinein_price']['child_name'].' - On the house';
                    $item['printername'] = [];
                    $item['labelprintname'] = [];
                    $item['kitchendescription'] = '';
                    $item['mainproductcomment'] = '';
                    $item['itemaddons'] = [];
                    $item['kitchenitemaddons'] = [];
                    $item['category'] = '';
                    $item['price'] = '0,00';
                    $item['original_price'] = 0;
                    $item['on_the_house'] = 1;
                    $item['void_id'] = 0;

                    $this->jsonItems[$this->jsonItemsIndex] = $item;
                    $this->jsonItemsIndex += 1;
                }
            }
            if (isset($all_you_eat_data['dinein_price']['dynamic_prices']) && ! empty($all_you_eat_data['dinein_price']['dynamic_prices'])) {
                foreach ($all_you_eat_data['dinein_price']['dynamic_prices'] as $dynamic_price_person) {
                    if ((int) $dynamic_price_person['on_the_house_person'] > 0) {
                        $item = $this->itemFormat;
                        $item['qty'] = @$dynamic_price_person['on_the_house_person'];
                        $item['itemname'] = @$dynamic_price_person['name'].' - On the house';
                        $item['printername'] = [];
                        $item['labelprintname'] = [];
                        $item['price'] = '0,00';
                        $item['original_price'] = 0;
                        $item['kitchendescription'] = '';
                        $item['mainproductcomment'] = '';
                        $item['itemaddons'] = [];
                        $item['kitchenitemaddons'] = [];
                        $item['category'] = '';
                        $item['on_the_house'] = 1;
                        $item['void_id'] = 0;

                        $this->jsonItems[$this->jsonItemsIndex] = $item;
                        $this->jsonItemsIndex += 1;
                    }
                }
            }
        }

        companionLogger('ayce item json prepared', $this->jsonItems);
    }

    protected function preparePaidOrderItems()
    {
        //only for paid orders
        if ($this->orderType != OrderTypes::PAID) {
            return;
        }

        // return id order items not found then return it
        if (! (isset($this->order['order_items']) && ! empty($this->order['order_items']))) {
            return;
        }

        $order = $this->order;

        //sort order items as per categories sequence
        $sortedOrderItems = [];
        if (! empty($this->categories)) {
            foreach ($this->categories as $category) {
                foreach ($order['order_items'] as $order_key => $order_item) {
                    if ($category->id == $order_item['product']['category_id']) {
                        $sortedOrderItems[] = $order_item;
                        unset($order['order_items'][$order_key]);
                    }
                }
            }
        }

        $order['order_items'] = $sortedOrderItems;
        $order_discount = (float) ($order['discount'] ?? 0);
        $categories = [];

        foreach ($order['order_items'] as $key => $item) {

            /*<--- if transfer = 1 then skip for print json ---->*/
            if (isset($item['transfer']) && $item['transfer'] == 1) {
                continue;
            }

            //return if product not found
            if (! (isset($item['product']) && ! empty($item['product']))) {
                return;
            }

            //declare global variables
            $isOnTheHouseProduct = 0;
            $isVoidProduct = 0;
            $is_euro_discount = 0;
            $item_discount = 0;

            //clone item format
            $newItem = $this->itemFormat;

            //set product qty
            $newItem['qty'] = ''.$item['quantity'];

            //show pcs if product al a carte setting is on and order is cart order
            $isProductAlACarte = $item['product']['is_al_a_carte'] ?? false;
            $productTotalPcs = (int) ($item['product']['total_pieces'] ?? 0);
            $productPostFix = '';
            if ($this->additionalSettings['show_no_of_pieces'] && $isProductAlACarte) {
                $productPostFix = ' | '.$productTotalPcs.($productTotalPcs > 0 ? 'pcs' : 'pc');
            }

            //set product name
            $newItem['itemname'] = ($item['product']['sku'] ? $item['product']['sku'].'.' : '').$item['product_name'].$productPostFix;

            //set on-the-house field & update product name respective it
            if (isset($item['on_the_house']) && $item['on_the_house'] == 1) {
                $isOnTheHouseProduct = 1;
                $newItem['on_the_house'] = 1;
                $newItem['itemname'] .= ' - on the house';
            }

            //set on-the-house field & update product name respective it
            if (isset($item['void_id']) && $item['void_id'] > 0) {
                $isVoidProduct = 1;
                $newItem['void_id'] = 1;
                $newItem['itemname'] .= ' - void';
            }

            //update product name if item wise discount is present
            $item_discount = (float) ($item['discount'] ?? 0);
            $is_euro_discount = isset($item['discount_type']) && $item['discount_type'] == 'EURO' ? 1 : 0;

            if ($order_discount == 0 && $item_discount > 0 && $isVoidProduct == 0 and $isOnTheHouseProduct == 0) {
                $newItem['itemname'] .= set_discount_with_prifix($is_euro_discount, $item_discount);
            }

            //prepare product categories setting array
            if (isset($item['product']['category']['id'])) {
                if (! in_array($item['product']['category']['id'], $categories)) {
                    //category settings
                    $category_settings[] = [
                        'id'          => strval($item['product']['category']['id']),
                        'by_category' => $item['product']['category']['kitchen_print_per_category'] == 1 ? true : false,
                        'by_product'  => $item['product']['category']['kitchen_print_per_product'] == 1 ? true : false,
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

            if (isset($item['extra']) && ! empty($item['extra'])) {
                $extra = json_decode($item['extra']);

                //set addon
                if ($extra->serve_type) {
                    $newItem['itemaddons'][] = $extra->serve_type;
                    $newItem['kitchenitemaddons'][] = $extra->serve_type;
                }

                if (isset($extra->weight) && $extra->weight && $extra->weight->item_weight) {
                    $newItem['itemaddons'][] = $extra->weight->item_weight.'g';
                    $newItem['kitchenitemaddons'][] = $extra->weight->item_weight.'g';
                    $item['unit_price'] = ((int) $extra->weight->item_weight * $item['unit_price']) / $extra->weight->product_weight;
                }

                if ($extra->size) {
                    $size_price = isset($extra->size->price) ? (float) $extra->size->price : 0;
                    $item_total += $size_price;
                    $current = __('messages.'.$extra->size->name);
                    $newItem['itemaddons'][] = $current;
                    $newItem['kitchenitemaddons'][] = $current;
                }

                if ($extra && $extra->supplements) {
                    foreach ($extra->supplements as $sup) {
                        if (isset($sup->sub_cat_id)) {
                            foreach ($sup->selected as $selected_sup) {
                                $item_total += ((float) ($selected_sup->val ?? 0)) * ((int) ($selected_sup->qty ?? 1));
                                $temp = '+ '.$selected_sup->name.((isset($selected_sup->qty) && $selected_sup->qty > 1) ? ' ('.$selected_sup->qty.'x)' : '');
                                $newItem['itemaddons'][] = $temp;
                                $newItem['kitchenitemaddons'][] = $temp;
                                if ($this->additionalSettings['show_supplement_kitchen_name'] && isset($selected_sup->id)) {
                                    $supplement_kitchen_name = Supplement::withTrashed()->where('id', $selected_sup->id)->first();
                                    if (! empty($supplement_kitchen_name) && $supplement_kitchen_name->alt_name != null && $supplement_kitchen_name->alt_name != '') {
                                        $newItem['kitchenitemaddons'][] = '  '.$supplement_kitchen_name->alt_name;
                                        //  $newItem['itemaddons'][] = '  '.$supplement_kitchen_name->alt_name;
                                    }
                                }
                            }
                        } else {
                            $item_total += ((float) ($sup->val ?? 0)) * ((int) ($sup->qty ?? 1));
                            $temp = '+ '.$sup->name.((isset($sup->qty) && $sup->qty > 1) ? ' ('.$sup->qty.'x)' : '');
                            $newItem['itemaddons'][] = $temp;
                            $newItem['kitchenitemaddons'][] = $temp;
                            if ($this->additionalSettings['show_supplement_kitchen_name'] && isset($sup->id)) {
                                $supplement_kitchen_name = Supplement::withTrashed()->where('id', $sup->id)->first();
                                if (! empty($supplement_kitchen_name) && $supplement_kitchen_name->alt_name != null && $supplement_kitchen_name->alt_name != '') {
                                    $newItem['kitchenitemaddons'][] = '  '.$supplement_kitchen_name->alt_name;
                                    //  $newItem['itemaddons'][] = '  '.$supplement_kitchen_name->alt_name;
                                }
                            }
                        }
                    }
                }

                $newItem['mainproductcomment'] = '';
                if (isset($item['comment']) && $item['comment'] != null && $item['comment'] != '') {
                    $newItem['mainproductcomment'] = $this->additionalSettings['show_product_comment_in_main_receipt'] == 1 ? $item['comment'] : '';
                    $newItem['comment'] = $item['comment'];
                }

                $newItem['price'] = ''.changePriceFormat(($item['unit_price'] + $item_total) * $item['quantity']);
                $newItem['original_price'] = ($item['unit_price'] + $item_total) * $item['quantity'];
                if ((isset($item['on_the_house']) && $item['on_the_house'] == 1) || (isset($item['void_id']) && $item['void_id'] > 0)) {
                    $newItem['price'] = '0';
                    $newItem['original_price'] = 0;
                }
            }

            /*set kitchen and label printer for each product*/
            $pro_kitchen = [];
            $kitchen = [];
            $label = [];
            $device_id = 0;
            $isPrintAddon = false;
            $saveOrderId = $order['saved_order_id'] ?? '';
            $skipKitchenLabelPrint = false;

            // no need to print if order is already saved
            if ($this->skipKitchenLabelPrint) {
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::DEFAULT && ! empty($saveOrderId)) {
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::MAIN) {
                // no need to print if only wat to reprint main print or kitchen print already printed
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::DEFAULT && ! empty($order['parent_id']) && $this->additionalSettings['dinein_guest_order'] == false) {
                // no need to print for round orders
                $skipKitchenLabelPrint = true;
            } elseif ($this->printType == PrintTypes::DEFAULT && $this->additionalSettings['is_print_cart_add'] == 1 && in_array($item['product']['category_id'], $this->additionalSettings['addon_print_categories'])) {
                // no need to print if printed already
                $skipKitchenLabelPrint = true;
            }
            //            if ($item['product']['category_id'] == '2373') {
//                dd($skipKitchenLabelPrint, PrintTypes::DEFAULT , $this->additionalSettings['is_print_cart_add']
//                    , $item['product']['category_id'], $this->additionalSettings['addon_print_categories'],in_array($item['product']['category_id'], $this->additionalSettings['addon_print_categories']));
//            }

            if ($skipKitchenLabelPrint == false) {
                if ($item['product']['printers']) {
                    $pro_kitchen = collect($item['product']['printers'])
                        ->pluck('printer_name')
                        ->toArray();
                }

                if (isset($item['product']['category']['is_takeaway_printer']) && $item['product']['category']['is_takeaway_printer'] == 1 && isset($item['product']['category']['takeaway_device_id']) && $item['product']['category']['takeaway_device_id']) {
                    $printer = Cache::tags([
                        FLUSH_ALL,
                        FLUSH_POS,
                        FLUSH_STORE_BY_ID.$this->store->id,
                        DEVICE_PRINTERS.$this->store->id,
                    ])
                        ->remember('{eat-card}-device-printers-'.$item['product']['category']['takeaway_printer_id'], caching_time, function () use ($item) {
                            return DevicePrinter::query()->where('id', $item['product']['category']['takeaway_printer_id'])
                                ->first();
                        });
                    $device_id = $item['product']['category']['takeaway_device_id'];
                    $printer_type = $item['product']['category']['takeaway_printer_type'];
                } else {
                    $printer = Cache::tags([
                        FLUSH_ALL,
                        FLUSH_POS,
                        FLUSH_STORE_BY_ID.$this->store->id,
                        DEVICE_PRINTERS.$this->store->id,
                    ])
                        ->remember('{eat-card}-device-printers-'.$item['product']['category']['printer_id'], caching_time, function () use ($item) {
                            return DevicePrinter::query()->where('id', $item['product']['category']['printer_id'])
                                ->first();
                        });
                    $device_id = $item['product']['category']['device_id'];
                    $printer_type = $item['product']['category']['printer_type'];
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
                        ->remember('{eat-card}-device-printer-kitchen-'.$device_id, caching_time, function () use ($item, $device_id) {
                            return DevicePrinter::query()->where('store_device_id', $device_id)
                                ->where('printer_type', 'kitchen')
                                ->get();
                        });
                    $label_printer = Cache::tags([
                        FLUSH_ALL,
                        FLUSH_POS,
                        FLUSH_STORE_BY_ID.$this->store->id,
                        DEVICE_PRINTERS.$this->store->id,
                    ])
                        ->remember('{eat-card}-device-printer-label-'.$device_id, caching_time, function () use ($item, $device_id) {
                            return DevicePrinter::query()->where('store_device_id', $device_id)
                                ->where('printer_type', 'label')
                                ->get();
                        });

                    $kitchen = $kitchen_printer ? $kitchen_printer->pluck('name')->toArray() : [];
                    $label = $label_printer ? $label_printer->pluck('name')->toArray() : [];
                } else {
                    $kitchen = [];
                    $label = [];
                }

                //set final kitchen and label printer
                $newItem['printername'] = ! empty($pro_kitchen) ? $pro_kitchen : (! empty($kitchen) ? $kitchen : []);
                $newItem['labelprintname'] = ! empty($label) ? $label : [];
            }

            //assign item to global items and increase index
            $this->jsonItems[$this->jsonItemsIndex] = $newItem;
            $this->jsonItemsIndex += 1;
        }

        $this->additionalSettings['categories_settings'] = $category_settings;
    }

    protected function sortItems()
    {
        if (empty($this->jsonItems)) {
            return;
        }

        $items = $this->jsonItems;
        $zeroPriceItems = [];
        $voidItems = [];
        $onTheHouseItems = [];
        $normalItems = [];
        $hideAndNotSortedItems = [];

        $hideFreeProducts = $this->additionalSettings['hide_free_product'];
        $hideVoidProducts = $this->additionalSettings['hide_void_product'];
        $hideOnTheHouseProducts = $this->additionalSettings['hide_onthehouse_product'];

//       dd($hideVoidProducts);

        foreach ($this->jsonItems as $keyItem=>$item) {
            if ($item['void_id'] == 0 && $item['on_the_house'] == 0 && $item['original_price'] > 0) {
                $normalItems[] = $item;
            } elseif ($item['void_id'] != 0 && $hideVoidProducts == 0) {
                $voidItems[] = $item;
            } elseif ($item['on_the_house'] == 1 && $hideOnTheHouseProducts == 0) {
                $onTheHouseItems[] = $item;
            } elseif ($item['original_price'] == 0 && $hideFreeProducts == 0 && $item['void_id'] == 0 && $item['on_the_house'] == 0) {
                $zeroPriceItems[] = $item;
            } else {
                $hideAndNotSortedItems[] = $item;
            }

            unset($items[$keyItem]);
        }

        $items = array_merge($normalItems, $voidItems, $onTheHouseItems, $zeroPriceItems);

        companionLogger('Eatcard companion hide or remove due to sort products : ', $hideAndNotSortedItems);

        //reassign sorted items
        $this->jsonItems = $items;
    }

    protected function preparePaymentReceipt()
    {
        if ($this->skipMainPrint) {
            return;
        }

        //return if setting is off
        if ($this->additionalSettings['show_order_transaction_detail'] == 0) {
            return;
        }

        $receipt = [];

        if ($this->orderType == OrderTypes::PAID) {

            //return if order not found
            if (empty($this->order)) {
                return;
            }

            if ($this->order['method'] == 'ccv') {
                $this->order['ccv_customer_receipt_decode'] = json_decode($this->order['ccv_customer_receipt'], true);
                if ($this->order['ccv_customer_receipt_decode']) {
                    foreach ($this->order['ccv_customer_receipt_decode'] as $temp) {
                        $receipt[] = $temp;
                    }
                }
            }

            if ($this->order['method'] == 'wipay') {
                $decoded = json_decode($this->order['worldline_customer_receipt'], true);
                $this->order['worldline_customer_receipt_decode'] = collect($decoded)
                    ->flatten()
                    ->reject(function ($value, $key) {
                        return $value == '0';
                    })
                    ->toArray();
                foreach ($this->order['worldline_customer_receipt_decode'] as $temp) {
                    $receipt[] = $temp;
                }
            }
        }

        $this->jsonReceipt = $receipt;
    }

    protected function prepareSummary()
    {
        if ($this->skipMainPrint) {
            return;
        }

        $summary = [];

        $sub_total = 0;
        $statiege_deposite_total = 0;
        $order_discount = 0;
        $discount_amount = 0;
        $is_euro_discount_order = 0;
        $discount_type_sign_with_amount = '';
        $total_tax = 0;
        $total_alcohol_tax = 0;
        $delivery_fee = 0;
        $additional_fee = 0;
        $plastic_bag_fee = 0;
        $coupon_price = 0;
        $cash_paid = 0;
        $method = '';
        $total_price = 0;
        $cash_changes = 0;
        $reservation_paid = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $sub_total = $this->order['sub_total'] ?? 0;
            $statiege_deposite_total = $this->order['statiege_deposite_total'] ?? 0;

            $order_discount = $this->order['discount'] ?? 0;
            $is_euro_discount_order = $this->order['discount_type'] == 'EURO' ? 1 : 0;
            $discount_amount = $this->order['discount_amount'] ?? 0;
            $discount_type_sign_with_amount = $order_discount > 0 ? ' '.set_discount_with_prifix($is_euro_discount_order, $order_discount) : '';

            $total_tax = $this->order['total_tax'] ?? 0;
            $total_alcohol_tax = $this->order['total_alcohol_tax'] ?? 0;
            $delivery_fee = $this->order['delivery_fee'] ?? 0;
            $additional_fee = $this->order['additional_fee'] ?? 0;
            $plastic_bag_fee = $this->order['plastic_bag_fee'] ?? 0;
            $coupon_price = $this->order['coupon_price'] ?? 0;
            $cash_paid = $this->order['cash_paid'] ?? 0;
            $method = $this->order['method'] ?? 0;
            $total_price = $this->order['total_price'] ?? 0;
            $reservation_paid = $this->order['reservation_paid'] ?? 0;
        }

        if ($sub_total > 0) {
            $summary[] = [
                'key'   => __('messages.sub_total'),
                'value' => ''.changePriceFormat($sub_total),
            ];
        }
        if ($statiege_deposite_total > 0) {
            $summary[] = [
                'key'   => 'Deposit',
                'value' => ''.changePriceFormat($statiege_deposite_total),
            ];
        }
        if ($discount_amount > 0) {
            $summary[] = [
                'key'   => __('messages.discount_amount').$discount_type_sign_with_amount,
                'value' => ''.changePriceFormat($discount_amount),
            ];
        }
        if ($total_tax > 0) {
            $summary[] = [
                'key'   => 'BTW laag',
                'value' => ''.changePriceFormat($total_tax),
            ];
        }
        if ($total_alcohol_tax > 0) {
            $summary[] = [
                'key'   => 'BTW hoog',
                'value' => ''.changePriceFormat($total_alcohol_tax),
            ];
        }
        if ($delivery_fee > 0) {
            $summary[] = [
                'key'   => 'Bezorgkosten',
                'value' => ''.changePriceFormat($delivery_fee),
            ];
        }
        if ($additional_fee > 0) {
            $summary[] = [
                'key'   => __('messages.additional_fees'),
                'value' => ''.changePriceFormat($additional_fee),
            ];
        }
        if ($plastic_bag_fee > 0) {
            $summary[] = [
                'key'   => __('messages.bag'),
                'value' => ''.changePriceFormat($plastic_bag_fee),
            ];
        }
        if ($coupon_price > 0) {
            $summary[] = [
                'key'   => __('messages.gift_voucher_cost'),
                'value' => ''.changePriceFormat($coupon_price),
            ];
        }
        if ($cash_paid > 0 && $method == 'cash') {
            $summary[] = [
                'key'   => __('messages.cash_paid_cost'),
                'value' => ''.changePriceFormat($cash_paid),
            ];
        }
        $cash_changes = $cash_paid - $total_price;
        if ($cash_paid > 0 && $cash_changes > 0 && $method == 'cash') {
            $summary[] = [
                'key'   => __('messages.cash_changes'),
                'value' => ''.changePriceFormat($cash_changes),
            ];
        }
        if ($reservation_paid > 0) {
            $summary[] = [
                'key'   => 'Booking deposit',
                'value' => ''.changePriceFormat($reservation_paid),
            ];
        }

        $this->jsonSummary = $summary;
    }
}
