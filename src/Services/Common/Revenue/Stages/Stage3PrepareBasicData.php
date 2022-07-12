<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

/**
 * @description Stag 3
 */
trait Stage3PrepareBasicData
{
    protected function prepareDefaultValue()
    {
        $this->detailJson = [
            [
                'key'   => '',
                'value' => '',
            ],
        ];

        $this->totalJson = [
            [
                'key'   => '',
                'value1' => '',
            ],
        ];

        $this->summaryJson = [
            [
                'title'   => '',
                'details' => [], //$this->detailJson,
                'total'   => [], //$this->totalJson,
            ],
        ];

        $this->matrixJson = [
            [
                'mtitle1'  => '',
                'mtitle2'  => '',
                'mtitle3'  => '',
                'mtitle4'  => '',
                'mdetails' => [
                    [
                        'value1' => '',
                        'value2' => '',
                        'value3' => '',
                        'value4' => '',
                    ],
                ],
            ],
        ];

        $this->finalJson = [
                'printername'   => '',
                'title1'        => '',
                'title2'        => '',
                'title3'        => '',
                'title4'        => '',
                'title5'        => '',
                'title6'        => '',
                'title7'        => '',
                'title8'        => '',
                'summarytop'    => [], //$this->summaryJson,
                'matrix'        => [], //$this->matrixJson,
                'summarybottom' => [], //$this->summaryJson,
                'thankyounote'  => [],
                'footertag'     => [],
        ];

        $this->additionalSettings = [

            //global settings
            'is_Print' => false,
            'is_PDF' => false,

            //store settings
            'third_party_revenue_status' => 0,
            'default_printer_name' => '',

            //kiosk device settings
            'kiosk_device_id_array' => [],

            //future date settings
            'future_date' => [],
            'future_date_arr' => [],

            //StorePosSetting
            'on_the_house_status' => false,
        ];

        $this->calcData = [

            //device vice
            'store_device_amount' => [],
            'kioskTotal' => [],

            //all
            'total_cash_orders' => 0,
            'total_pin_orders' => 0,
            'total_ideal_orders' => 0,
            'thusibezorgd_orders' => 0,
            'ubereats_orders' => 0,
            'deliveroo_orders' => 0,
            'total_orders' => 0,
            'total_kiosk' => 0,
            'product_count' => 0,
            'third_party_product_count' => 0,
            'on_the_house_item_total' => 0,
            'void_order' => 0,
            'final_revenue' => 0,
            'number_of_cashdrawer_open' => 0,

            'dates' => [],

            'total_cash_amount' => 0,

            'total_pin_amount' => 0,

            'total_ideal_amount' => 0,

            'thusibezorgd_orders_amount' => 0,

            'ubereats_orders_amount' => 0,

            'deliveroo_orders_amount' => 0,

            'total_takeaway' => 0,

            'total_dine_in' => 0,

            'plastic_bag_fee_total' => 0,
            'plastic_bag_fee_total_date' => [],

            'delivery_fee_total' => 0,
            'delivery_fee_total_date' => [],

            'additional_fee_total' => 0,
//            'additional_fee_total_date' => [],

            'deposite_total' => 0,
            'total_tip_amount' => 0,
//            'deposite_total_date' => [],

            'reservation_received_total' => 0,
            'reservation_received_total_date' => [],

            'reservation_refund_total' => 0,
            'reservation_refund_total_date' => [],

            'reservation_deducted_total' => 0,
//            'reservation_deducted_total_date' => [],

            'total_third_party_total' => 0,
            'total_third_party_total_date' => [],

            'total_amount_inc_tax' => 0,
            'total_amount_inc_tax_date' => [],

            'total_amount_without_tax' => 0,
            'total_amount_without_tax_date' => [],

//            'total_0_inc_tax_subtotal' => 0,
//            'total_0_inc_tax_subtotal_date' => [],
//
//            'total_9_inc_tax_subtotal' => 0,
//            'total_9_inc_tax_subtotal_date' => [],
//
//            'total_21_inc_tax_subtotal' => 0,
//            'total_21_inc_tax_subtotal_date' => [],

            'total_0_without_tax_subtotal' => 0,
//            'total_0_without_tax_subtotal_date' => [],

            'total_9_without_tax_subtotal' => 0,
            'total_9_without_tax_subtotal_date' => [],

            'total_21_without_tax_subtotal' => 0,
            'total_21_without_tax_subtotal_date' => [],

            'total_9_tax' => 0, //$temp_btw_9
            'total_9_tax_date' => [],

            'total_21_tax' => 0, //$temp_btw_21
            'total_21_tax_date' => [],

            'total_discount_inc_tax' => 0,
            'total_discount_inc_tax_date' => [],

            'total_discount_without_tax' => 0,
            'total_discount_without_tax_date' => [],

//            'total_9_discount_inc_tax' => 0,
//            'total_9_discount_inc_tax_date' => [],

//            'total_21_discount_inc_tax' => 0,
//            'total_21_discount_inc_tax_date' => [],

            'total_9_discount_without_tax' => 0,
            'total_9_discount_without_tax_date' => [],

            'total_21_discount_without_tax' => 0,
            'total_21_discount_without_tax_date' => [],

            'coupon_used_price' => 0,
            'coupon_used_price_date' => [],

            'total_gift_card_count' => 0,
            'total_gift_card_count_date' => [],

            'total_gift_card_amount' => 0,
            'total_gift_card_amount_date' => [],

            'total_turn_over_with_tax' => 0,
            'total_turn_over_with_tax_date' => [],

            'total_turn_over_without_tax' => 0,
            'total_turn_over_without_tax_date' => [],

            'total_tax' => 0,
            'total_tax_date' => [],

            'total_9_without_tax_discount_subtotal' => 0,
            'total_21_without_tax_discount_subtotal' => 0,
            'total_9_inc_tax_without_discount_subtotal' => 0,
            'total_21_inc_tax_without_discount_subtotal' => 0,

            'original_total_amount' => 0,
            'final_total_amount' => 0,
            'final_pin_ideal_amount' => 0,
            'final_pin_ideal_cash_amount' => 0,
            'final_total_orders' => 0,
            'final_product_count' => 0,
            'final_avg' => 0,

            //testing
            'order' => [],
        ];

        $this->finalData = [

        ];

        $this->finalOrderDetail = [

        ];
    }
}
