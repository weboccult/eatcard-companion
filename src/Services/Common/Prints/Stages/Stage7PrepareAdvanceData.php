<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PaymentSplitTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\DevicePrinter;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionPrintTrans;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\set_discount_with_prifix;

/**
 * @description Stag 7
 * @mixin BaseGenerator
 */
trait Stage7PrepareAdvanceData
{
    /**
     * @return void
     * set order number base on store takeaway setting.
     * it was use only for Paid and Sub orders.
     */
    protected function prepareDynamicOrderNo()
    {
        $dynamicOrderNo = '';
        if (in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            if (! empty($this->additionalSettings['print_dynamic_order_no'])) {
                $dynamicOrderNo = ''.substr(($this->order['order_id'] ?? ''), -3);
            } else {
                $dynamicOrderNo = ''.substr(($this->order['order_id'] ?? ''), -2);
            }
        }

        $this->advanceData['dynamicOrderNo'] = trim($dynamicOrderNo);
    }

    /**
     * @return void
     * Set table name base on related order and reservation data
     * there are multiple system related condition.
     * it will use on Main and kitchen print both
     */
    protected function prepareTableName()
    {
        $tableName = '';
        if ($this->orderType == OrderTypes::PAID) {
            $tableName = $this->order['table_name'] ?? '';

            if ($this->systemType == SystemTypes::KDS) {
                $tableName = ! empty($this->advanceData['dynamicOrderNo']) ? ('#'.$this->advanceData['dynamicOrderNo']) : '';
            } elseif ($this->additionalSettings['dinein_guest_order'] && ! empty($tableName)) {
                $tableName = ($this->order['table_name']) ? __companionPrintTrans('general.table_name').' '.$this->order['table_name'] : '';
            }
        }

        if (! empty($this->reservation)) {
            if (isset($this->reservation['tables2']) && $this->reservation['tables2']->count() > 0) {
                $tables = $this->reservation['tables2']->pluck('name')->toArray();
                $tableName = implode(',', $tables);
            }
        }

        if ($this->orderType == OrderTypes::RUNNING && ! empty($this->reservationOrderItems)) {
            $tableName = 'Table #'.($this->reservationOrderItems->table->name ?? '');
        }
        $this->advanceData['tableName'] = $tableName;
    }

    /**
     * @return void
     * set related order data
     * update data format and other condition for modify data will be applied here.
     */
    protected function processOrderData()
    {
        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['paid_on'])) {
                $this->order['paid_on'] = Carbon::parse($this->order['paid_on'])->format('d-m-Y H:i');
            }

            if (isset($this->order['order_date'])) {
                $this->order['order_date'] = Carbon::parse($this->order['order_date'])->format('d-m-Y');
            }

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

        if ($this->orderType == OrderTypes::RUNNING && ! empty($this->reservation)) {
            $this->reservation['order_date'] = Carbon::parse($this->reservation->getRawOriginal('res_date'))->format('d-m-Y');
        }

        if ($this->orderType == OrderTypes::SUB) {
            if (isset($this->subOrder['paid_on'])) {
                $this->subOrder['paid_on'] = Carbon::parse($this->subOrder['paid_on'])->format('d-m-Y H:i');
            }
            if (isset($this->subOrder['order_date'])) {
                $this->subOrder['order_date'] = Carbon::parse($this->subOrder['order_date'])->format('d-m-Y');
            }
        }
    }

    /**
     * @return void
     * set third party name prefix here
     */
    protected function setThirdPartyName()
    {
        $thirdPartyName = '';

        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['thusibezorgd_order_id']) && ! empty($this->order['thusibezorgd_order_id'])) {
                $this->systemType = SystemTypes::THUSIBEZORGD;
                $thirdPartyName = 'Thuisbezorgd: ';
            } elseif (isset($this->order['uber_eats_order_id']) && ! empty($this->order['uber_eats_order_id'])) {
                $this->systemType = SystemTypes::UBEREATS;
                $thirdPartyName = 'Uber: ';
            } elseif (isset($this->order['deliveroo_order_id']) && ! empty($this->order['deliveroo_order_id'])) {
                $this->systemType = SystemTypes::DELIVEROO;
                $thirdPartyName = 'Deliveroo: ';
            }
        }

        $this->additionalSettings['thirdPartyName'] = $thirdPartyName;
    }

    /**
     * @return void
     * set full recipt will be printed or not base on store & device settings, and type of print
     */
    public function prepareFullReceiptFlag()
    {
        if ($this->skipMainPrint) {
            return;
        }

        //skip exclude print for profoma print
        if ($this->printType == PrintTypes::PROFORMA) {
            $this->additionalSettings['fullreceipt'] = '1';

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
        } elseif ($this->systemType == SystemTypes::KIOSK) {
            //If kiosk is selected in exclude print then remove kiosk print in POS protocol print
            $excludeSystemName = ! $this->additionalSettings['exclude_print_status'] ? 'kiosk' : '';
        } elseif ($this->systemType == SystemTypes::THUSIBEZORGD) {
            $excludeSystemName = 'thusibezorgd';
        } elseif ($this->systemType == SystemTypes::UBEREATS) {
            $excludeSystemName = 'ubereats';
        } elseif ($this->systemType == SystemTypes::DELIVEROO) {
            $excludeSystemName = 'deliveroo';
        }

        $excludePrintSystem = ! empty($this->additionalSettings['exclude_print_from_main_print']) ? explode(',', $this->additionalSettings['exclude_print_from_main_print']) : [];
        if (! empty($excludePrintSystem) && in_array($excludeSystemName, $excludePrintSystem)) {
            $fullReceipt = '0';
        }

        $this->additionalSettings['fullreceipt'] = $fullReceipt;
    }

    /**
     * @return void
     * Prepare AYCE items for all type of order.
     * It will print only on Main receipt, so we not prepare if Main print is Skied.
     * calculated manual tax nd order price for Proforma and save order.
     * need to skip for sub order item wise split order, because for that type of order we not allow it
     */
    public function prepareAYCEItems()
    {
        // no need to prepare ayce item if main print will not print
        if ($this->skipMainPrint) {
            return;
        }

        if ($this->orderType == OrderTypes::SUB && isset($this->order['payment_split_type']) && $this->order['payment_split_type'] == PaymentSplitTypes::PRODUCT_SPLIT) {
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

            $this->itemPricesCalculate($item, true);
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

            $this->itemPricesCalculate($item, true);
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

                        $this->itemPricesCalculate($item, true);
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

                $this->itemPricesCalculate($item, true);
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

                    $this->itemPricesCalculate($item, true);
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

                $this->itemPricesCalculate($item, true);
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

                $this->itemPricesCalculate($item, true);
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

                            $this->itemPricesCalculate($item, true);
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

                    $this->itemPricesCalculate($item, true);
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

                        $this->itemPricesCalculate($item, true);
                        $this->jsonItems[$this->jsonItemsIndex] = $item;
                        $this->jsonItemsIndex += 1;
                    }
                }
            }
        }

//        companionLogger('----Companion Print : ayce item json prepared', $this->jsonItems);
    }

    /**
     * @return void
     * prepare order items details for print in Main and Kitchen print.
     * skip for save and running order
     * here we will modify item name base on product settings,
     * modify item name with add void, on-the-house, discount 's postfix.
     * set Kitchen and Label printer for print it based on there print type and settings.
     */
    protected function preparePaidOrderItems()
    {
        //only for paid orders
        if (! in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            return;
        }

        $order = [];
        $orderItems = [];
        $isSubOrderItems = false;

        if ($this->orderType == OrderTypes::SUB && isset($this->order['payment_split_type']) && $this->order['payment_split_type'] == PaymentSplitTypes::PRODUCT_SPLIT) {
            $isSubOrderItems = true;
            // return id suborder items not found then return it
            if (! (isset($this->subOrder['sub_order_items']) && ! empty($this->subOrder['sub_order_items']))) {
                return;
            }
            $order = $this->subOrder;
            if (isset($order['order_items'])) {
                unset($order['order_items']);
            }
            $order['order_items'] = $order['sub_order_items'];
        } else {
            // return id order items not found then return it
            if (! (isset($this->order['order_items']) && ! empty($this->order['order_items']))) {
                return;
            }
            $order = $this->order;
        }

        //sort order items as per categories sequence
        $sortedItems = [];
        if (! empty($this->categories)) {
            foreach ($this->categories as $category) {
                foreach ($order['order_items'] as $order_key => $order_item) {
                    if ($category->id == $order_item['product']['category_id']) {
                        $sortedItems[] = $order_item;
                        unset($order['order_items'][$order_key]);
                    }
                }
            }
        }

        $order['order_items'] = $sortedItems;
        $order_discount = (float) ($order['discount'] ?? 0);
        $categories = [];
        $category_settings = [];

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

            //set not discount product for created from takeaway orders
            if ($this->order['created_from'] == 'takeaway' && $order_discount > 0 && $item_discount == 0) {
                $newItem['itemname'] .= ' *';
                $this->advanceData['show_discount_note'] = 'Note :- * This product(s) will be excluded from discount.';
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

            // set product kitchen description
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
                    $current = __companionPrintTrans('print.'.$extra->size->name);
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

            //need to store both for item wise split
            if ($isSubOrderItems) {
                if ($item['tax_percentage'] == 21 && (int) $item['on_the_house'] == 0 && (int) $item['void_id'] == 0) {
                    $this->total_21_tax_amount += (float) $newItem['original_price'] - (float) $item['discount_inc_tax'] - (float) $item['statiege_deposite_total'];
                } elseif ($item['tax_percentage'] == 9 && (int) $item['on_the_house'] == 0 && (int) $item['void_id'] == 0) {
                    $this->total_9_tax_amount += (float) $newItem['original_price'] - (float) $item['discount_inc_tax'] - (float) $item['statiege_deposite_total'];
                }
            } else {
                //calculate 21% tax amount for print in tax summary
                if ($item['tax_percentage'] == 21 && (int) $item['on_the_house'] == 0 && (int) $item['void_id'] == 0) {
                    $this->total_21_tax_amount += (float) $item['total_price'] - (float) $item['statiege_deposite_total'];
                }
            }

            /*set kitchen and label printer for each product*/
            $kds_Kitchen = [];
            $pro_kitchen = [];
            $kitchen = [];
            $label = [];
            $device_id = 0;
            $isPrintAddon = false;
            $saveOrderId = $order['saved_order_id'] ?? '';
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
            } elseif ($this->printMethod == PrintMethod::PROTOCOL && $this->systemType == SystemTypes::KIOSK) {
                //skip kitchen print for protocol print, It will be print in sqs print of pos
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

            if ($this->orderType == OrderTypes::SUB && isset($order['status']) && $order['status'] != 'paid' && ! $isSubOrderItems) {
                //skip kitchen print if custome or equal split sub order is not paid.
                $skipKitchenLabelPrint = true;
            }

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
                        ->remember('{eat-card}-companion-device-printers-'.$item['product']['category']['takeaway_printer_id'], CACHING_TIME, function () use ($item) {
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
                        ->remember('{eat-card}-companion-device-printers-'.$item['product']['category']['printer_id'], CACHING_TIME, function () use ($item) {
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
                        ->remember('{eat-card}-companion-device-printer-kitchen-'.$device_id, CACHING_TIME, function () use ($item, $device_id) {
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
                        ->remember('{eat-card}-companion-device-printer-label-'.$device_id, CACHING_TIME, function () use ($item, $device_id) {
                            return DevicePrinter::query()
                                ->where('store_device_id', $device_id)
                                ->where('printer_type', 'label')
                                ->get();
                        });
                    $kitchen = $kitchen_printer ? $kitchen_printer->pluck('name')->toArray() : [];
                    $label = $label_printer ? $label_printer->pluck('name')->toArray() : [];
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
        // code in individual generator file
    }

    /**
     * @return void
     * prepare Save items details for print in Main and Kitchen print.
     * skip for Paid, Sub and running order
     * here we will modify item name base on product settings,
     * modify item name with add void, on-the-house, discount 's postfix.
     * set Kitchen and Label printer for print it based on there print type and settings.
     */
    protected function prepareSaveOrderItems()
    {
        // code in individual generator file
    }

    /**
     * @return void
     * Sort Item in sequence of Paid, Void, On-The-House, Free/Zero Price Product
     */
    protected function sortItems()
    {
        if (empty($this->jsonItems)) {
            return;
        }

        if ($this->orderType == OrderTypes::RUNNING && $this->printType != PrintTypes::PROFORMA) {
            // skip for kitchen print
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

        foreach ($this->jsonItems as $keyItem=>$item) {
            // negative price consider for return orders
            if ($item['void_id'] == 0 && $item['on_the_house'] == 0 && ($item['original_price'] > 0 || $item['original_price'] < 0)) {
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

        //reassign sorted items
        $this->jsonItems = $items;
    }

    /**
     * @return void
     * Attach ccv/wpay payment receipt details in footer of print if setting is on.
     */
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
        $order = [];

        if (in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            if ($this->orderType == OrderTypes::PAID) {
                $order = $this->order;
            } elseif ($this->orderType == OrderTypes::SUB) {
                $order = $this->subOrder;
            }

            //return if order not found
            if (empty($order)) {
                return;
            }

            if ($order['method'] == 'ccv') {
                $order['ccv_customer_receipt_decode'] = json_decode($order['ccv_customer_receipt'], true);
                if ($order['ccv_customer_receipt_decode']) {
                    foreach ($order['ccv_customer_receipt_decode'] as $temp) {
                        $receipt[] = $temp;
                    }
                }
            }

            if ($order['method'] == 'wipay') {
                $decoded = json_decode($order['worldline_customer_receipt'], true);
                $order['worldline_customer_receipt_decode'] = collect($decoded)
                    ->flatten()
                    ->reject(function ($value, $key) {
                        return $value == '0';
                    })
                    ->toArray();
                foreach ($order['worldline_customer_receipt_decode'] as $temp) {
                    $receipt[] = $temp;
                }
            }
        }

        $this->jsonReceipt = $receipt;
    }

    /**
     * @return void
     * Prepare summary based or related order data
     */
    protected function preparePreSummary()
    {
        if ($this->skipMainPrint) {
            return;
        }

        $summary = [];

        $statiege_deposite_total = 0;
        $delivery_fee = 0;
        $additional_fee = 0;
        $plastic_bag_fee = 0;
        $tip_amount = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $statiege_deposite_total = $this->order['statiege_deposite_total'] ?? 0;
            $delivery_fee = $this->order['delivery_fee'] ?? 0;
            $additional_fee = $this->order['additional_fee'] ?? 0;
            $plastic_bag_fee = $this->order['plastic_bag_fee'] ?? 0;
            $tip_amount = $this->order['tip_amount'] ?? 0;
        } elseif ($this->orderType == OrderTypes::SUB && ! empty($this->order) && ! empty($this->subOrder)) {
//            $statiege_deposite_total = ($this->subOrder['total_price'] * $this->order['statiege_deposite_total']) / $this->order['total_price'];
            $statiege_deposite_total = $this->subOrder['statiege_deposite_total'] ?? 0;
            $delivery_fee = $this->subOrder['delivery_fee'] ?? 0;
            $additional_fee = $this->subOrder['additional_fee'] ?? 0;
            $tip_amount = $this->subOrder['tip_amount'] ?? 0;
        }

        if ($statiege_deposite_total > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.deposit'),
                'value' => ''.changePriceFormat($statiege_deposite_total),
            ];
        }
        if ($delivery_fee > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.delivery_fee'),
                'value' => ''.changePriceFormat($delivery_fee),
            ];
        }
        if ($additional_fee > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.additional_fees'),
                'value' => ''.changePriceFormat($additional_fee),
            ];
        }
        if ($plastic_bag_fee > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.plastic_bag'),
                'value' => ''.changePriceFormat($plastic_bag_fee),
            ];
        }
        if ($tip_amount > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.tip'),
                'value' => ''.changePriceFormat($tip_amount),
            ];
        }

        $this->jsonPreSummary = $summary;
    }

    /**
     * @return void
     * Prepare summary based or related order data
     */
    protected function prepareSubTotal()
    {
        if ($this->skipMainPrint) {
            return;
        }
        $subTotal = [];
        $sub_total = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $sub_total = $this->order['original_order_total'] ?? 0;
//            $sub_total = ($this->order['total_price'] ?? 0) + ($this->order['discount_inc_tax'] ?? 0);
        } elseif ($this->orderType == OrderTypes::SUB && ! empty($this->order) && ! empty($this->subOrder)) {
            $sub_total = ($this->subOrder['total_price'] ?? 0) + ($this->subOrder['discount_inc_tax'] ?? 0);
        }

        if ($sub_total > 0) {
            $subTotal[] = [
                'key'   => __companionPrintTrans('print.sub_total'),
                'value' => ''.changePriceFormat($sub_total),
            ];
        }
        $this->jsonSubTotal = $subTotal;
    }

    /**
     * @return void
     * Prepare tax summary based or related order data
     */
    protected function prepareTaxDetail()
    {
        if ($this->skipMainPrint) {
            return;
        }

        //for third party order we are not showing tax details
        if (! empty($this->additionalSettings['thirdPartyName'])) {
            return;
        }

        $total_0_tax = 0;
        $total_9_tax = 0;
        $total_21_tax = 0;

        $total_0_tax_amount = 0;
        $total_9_tax_amount = 0;
        $total_21_tax_amount = 0;

        $total_price = 0;
        $reservation_paid = 0;
        $coupon_price = 0;
        $statiege_deposite_total = 0;
        $additional_fee = 0;
        $delivery_fee = 0;
        $plastic_bag_fee = 0;
        $tip_amount = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $total_price = $this->order['total_price'] ?? 0;
            $statiege_deposite_total = $this->order['statiege_deposite_total'] ?? 0;
            $delivery_fee = $this->order['delivery_fee'] ?? 0;
            $additional_fee = $this->order['additional_fee'] ?? 0;
            $plastic_bag_fee = $this->order['plastic_bag_fee'] ?? 0;
            $tip_amount = $this->order['tip_amount'] ?? 0;
            $coupon_price = $this->order['coupon_price'] ?? 0;
            $reservation_paid = $this->order['reservation_paid'] ?? 0;

            $total_0_tax_amount = $statiege_deposite_total;
            $total_21_tax_amount = $this->total_21_tax_amount + $delivery_fee + $plastic_bag_fee;
            $total_9_tax_amount = ($total_price + $reservation_paid + $coupon_price)
                                - ($total_21_tax_amount + $additional_fee + $tip_amount + $statiege_deposite_total);
        } elseif ($this->orderType == OrderTypes::SUB && ! empty($this->order) && ! empty($this->subOrder)) {
            $statiege_deposite_total = $this->subOrder['statiege_deposite_total'] ?? 0;
            //                $statiege_deposite_total = ($this->subOrder['total_price'] * $this->order['statiege_deposite_total']) / $this->order['total_price'];
            $total_0_tax_amount = $statiege_deposite_total;
            if ($this->order['payment_split_type'] == PaymentSplitTypes::PRODUCT_SPLIT) {
                $total_21_tax_amount = $this->total_21_tax_amount ?? 0;
                $total_9_tax_amount = $this->total_9_tax_amount ?? 0;
            } else {
                $total_21_tax_amount = $this->subOrder['alcohol_product_total'] ?? 0;
                $total_9_tax_amount = $this->subOrder['normal_product_total'] ?? 0;
            }
        } elseif ($this->orderType == OrderTypes::SAVE) {
            //note : here all variable are declared in individual generator class file
            $total_0_tax_amount = $this->total_deposit ?? 0;
            $total_9_tax_amount = ($this->normal_sub_total ?? 0) + ($this->total_tax ?? 0);
            $total_21_tax_amount = ($this->alcohol_sub_total ?? 0) + ($this->total_alcohol_tax ?? 0);
        } elseif ($this->orderType == OrderTypes::RUNNING) {
            $total_0_tax_amount = $this->total_deposit ?? 0;
            $total_9_tax_amount = ($this->normal_sub_total ?? 0) + ($this->total_tax ?? 0);
            $total_21_tax_amount = ($this->alcohol_sub_total ?? 0) + ($this->total_alcohol_tax ?? 0);
        }

        $total_9_tax = ($total_9_tax_amount * 9) / 109;
        $total_21_tax = ($total_21_tax_amount * 21) / 121;

        $this->jsonTaxDetail = [
            [
                'column1' => 'Tax',
                'column2' => 'Over',
                'column3' => '',
                'column4' => 'tax',
            ],
            [
                'column1' => '0%',
                'column2' => ''.changePriceFormat($total_0_tax_amount),
                'column3' => '',
                'column4' => '0,00',
            ],
            [
                'column1' => '9%',
                'column2' => ''.changePriceFormat($total_9_tax_amount),
                'column3' => '',
                'column4' => ''.changePriceFormat($total_9_tax),
            ],
            [
                'column1' => '21%',
                'column2' => ''.changePriceFormat($total_21_tax_amount),
                'column3' => '',
                'column4' => ''.changePriceFormat($total_21_tax),
            ],
            [
                'column1' => 'Total',
                'column2' => ''.changePriceFormat($total_0_tax_amount + $total_9_tax_amount + $total_21_tax_amount),
                'column3' => '',
                'column4' => ''.changePriceFormat($total_9_tax + $total_21_tax),
            ],
        ];
    }

    /**
     * @return void
     * Prepare summary based or related order data
     */
    protected function prepareGeneralComments()
    {
        if ($this->skipMainPrint) {
            return;
        }

        if (! empty($this->advanceData['show_discount_note'])) {
            $this->jsonGeneralComments[] = [
                'column1' => $this->advanceData['show_discount_note'],
                'column2' => '',
                'column3' => '',
                'column4' => '',
            ];
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

        $order_discount = 0;
        $discount_amount = 0;
        $is_euro_discount_order = 0;
        $discount_type_sign_with_amount = '';
        $coupon_price = 0;
        $reservation_paid = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $order_discount = $this->order['discount'] ?? 0;
            $is_euro_discount_order = $this->order['discount_type'] == 'EURO' ? 1 : 0;
            $discount_amount = $this->order['discount_inc_tax'] ?? 0;
            $discount_type_sign_with_amount = $order_discount > 0 ? ' '.set_discount_with_prifix($is_euro_discount_order, $order_discount) : '';
            $coupon_price = $this->order['coupon_price'] ?? 0;
            $reservation_paid = $this->order['reservation_paid'] ?? 0;
            $remaining_Coupon_price = GiftPurchaseOrder::where('id', $this->order['gift_purchase_id'])->first();
        } elseif ($this->orderType == OrderTypes::SUB && ! empty($this->order) && ! empty($this->subOrder)) {
            $order_discount = $this->subOrder['discount'] ?? 0;
            $is_euro_discount_order = $this->subOrder['discount_type'] == 'EURO' ? 1 : 0;
            $discount_amount = $this->subOrder['discount_inc_tax'] ?? 0;
            $discount_type_sign_with_amount = $order_discount > 0 ? ' '.set_discount_with_prifix($is_euro_discount_order, $order_discount) : '';
            $coupon_price = $this->subOrder['coupon_price'] ?? 0;
            $reservation_paid = $this->subOrder['reservation_paid'] ?? 0;
            $remaining_Coupon_price = GiftPurchaseOrder::where('id', $this->subOrder['gift_purchase_id'])->first();
        }

        if ($discount_amount > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.discount_amount').$discount_type_sign_with_amount,
                'value' => '-'.changePriceFormat($discount_amount),
            ];
        }

        if ($reservation_paid > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.reservation_deposit'),
                'value' => '-'.changePriceFormat($reservation_paid),
            ];
        }

        if ($coupon_price > 0) {
            $summary[] = [
                'key'   => __companionPrintTrans('print.gift_voucher_cost').'('.changePriceFormat($remaining_Coupon_price->remaining_price ?? 0).')',
                'value' => '-'.changePriceFormat($coupon_price),
            ];
        }

        $this->jsonSummary = $summary;
    }

    /**
     * @return void
     * Prepare payment summary based or related order data
     */
    protected function preparePaymentSummary()
    {
        if ($this->skipMainPrint) {
            return;
        }

        $summary = [];

        $cash_paid = 0;
        $method = '';
        $total_price = 0;
        $cash_changes = 0;
        $cash_received = 0;

        if ($this->orderType == OrderTypes::PAID && ! empty($this->order)) {
            $cash_paid = $this->order['cash_paid'] ?? 0;
            $method = $this->order['method'] ?? '';
            $total_price = $this->order['total_price'] ?? 0;
        } elseif ($this->orderType == OrderTypes::SUB && ! empty($this->order) && ! empty($this->subOrder)) {
            $cash_paid = $this->subOrder['cash_paid'] ?? 0;
            $method = $this->subOrder['method'] ?? '';
            $total_price = $this->subOrder['total_price'] ?? 0;

            if ($this->order['payment_split_type'] == PaymentSplitTypes::EQUAL_SPLIT && $this->order['payment_split_persons']) {
                $split_no = ($this->subOrder['split_no']) ? ''.$this->subOrder['split_no'] : '';
                $summary[] = [
                    'key'   => __companionPrintTrans('print.split'),
                    'value' => $split_no.'/'.$this->order['payment_split_persons'],
                ];
            }
        }

        if ($method == 'cash') {
            $cash_changes = $cash_paid > 0 ? ($cash_paid - $total_price) : 0;
            $cash_received = $total_price + $cash_changes;
            $summary[] = [
               'key'   => __companionPrintTrans('print.payment_by'),
               'value' => __companionPrintTrans('print.cash'),
           ];

            $summary[] = [
                'key'   => __companionPrintTrans('print.cash_paid_cost'),
                'value' => ''.changePriceFormat($cash_received),
            ];

            if ($cash_paid > 0 && $cash_changes > 0) {
                $summary[] = [
                    'key'   => __companionPrintTrans('print.cash_changes'),
                    'value' => ''.changePriceFormat($cash_changes),
                ];
            }
        }

        $this->jsonPaymentSummary = $summary;
    }

    protected function prepareViewName()
    {
        if (! in_array($this->printMethod, [PrintMethod::PDF, PrintMethod::HTML])) {
            return;
        }

        if ($this->takeawayEmailType == 'user') {
            $this->advanceData['viewPath'] = 'takeaway.takeaway-order-user';
        } elseif ($this->takeawayEmailType == 'owner') {
            $this->advanceData['viewPath'] = 'takeaway.takeaway-order-owner-new';
        } else {
            $this->advanceData['viewPath'] = 'order.order-details';
        }
    }
}
