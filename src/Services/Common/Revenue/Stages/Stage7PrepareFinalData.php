<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionPrintTrans;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;

/**
 * @description Stag 7
 * @mixin BaseGenerator
 */
trait Stage7PrepareFinalData
{
    protected function setCommonData()
    {
        $this->finalData['current_date_time'] = Carbon::now()->format('d-m-Y H:i');

//        $this->finalData['kioskTotal'] = $this->calcData['kioskTotal'];
        $this->finalData['on_the_house_status'] = $this->additionalSettings['on_the_house_status'];
        $this->finalData['number_of_cashdrawer_open'] = $this->calcData['number_of_cashdrawer_open'];
        $this->finalData['third_party_print_status'] = $this->additionalSettings['third_party_revenue_status'];

        $this->finalData['reservation_received_total'] = changePriceFormat($this->calcData['reservation_received_total']);
        $this->finalData['reservation_refund_total'] = changePriceFormat($this->calcData['reservation_refund_total']);

        $this->finalData['total_9_tax'] = changePriceFormat($this->calcData['total_9_tax']);
        $this->finalData['total_21_tax'] = changePriceFormat($this->calcData['total_21_tax']);
        $this->finalData['coupon_price'] = changePriceFormat($this->calcData['coupon_used_prince']);

        $this->finalData['gift_card_order_count'] = $this->calcData['total_gift_card_count'];
        $this->finalData['total_cash_orders'] = ($this->calcData['total_cash_orders']);
        $this->finalData['total_pin_orders'] = ($this->calcData['total_pin_orders']);
        $this->finalData['total_ideal_orders'] = ($this->calcData['total_ideal_orders']);
        $this->finalData['total_takeaway'] = changePriceFormat($this->calcData['total_takeaway']);
        $this->finalData['total_dine_in'] = changePriceFormat($this->calcData['total_dine_in']);
        $this->finalData['total_kiosk'] = changePriceFormat($this->calcData['total_kiosk']);
        $this->finalData['thusibezorgd_orders'] = changePriceFormat($this->calcData['thusibezorgd_orders']);
        $this->finalData['ubereats_orders'] = changePriceFormat($this->calcData['ubereats_orders']);
        $this->finalData['total_orders'] = ($this->calcData['final_total_orders']);

        $this->finalData['products_count'] = ($this->calcData['final_product_count']);

        $this->finalData['plastic_bag_fee'] = changePriceFormat($this->calcData['plastic_bag_fee_total']);
        $this->finalData['delivery_fee'] = changePriceFormat($this->calcData['delivery_fee_total']);
        $this->finalData['additional_fee'] = changePriceFormat($this->calcData['additional_fee_total']);

        $this->finalData['total_cash_amount'] = changePriceFormat($this->calcData['total_cash_amount']);
        $this->finalData['total_pin_amount'] = changePriceFormat($this->calcData['total_pin_amount']);
        $this->finalData['total_ideal_amount'] = changePriceFormat($this->calcData['total_ideal_amount']);
        $this->finalData['total_gift_card_amount'] = changePriceFormat($this->calcData['total_gift_card_amount']);
        $this->finalData['thusibezorgd_amount'] = changePriceFormat($this->calcData['thusibezorgd_orders_amount']);
        $this->finalData['ubereats_amount'] = changePriceFormat($this->calcData['ubereats_orders_amount']);

        $this->finalData['final_total'] = changePriceFormat($this->calcData['final_total_amount']);
        $this->finalData['total_pin_ideal_amount'] = changePriceFormat($this->calcData['final_pin_ideal_amount']);
        $this->finalData['total_pin_ideal_cash_amount'] = changePriceFormat($this->calcData['final_pin_ideal_cash_amount']);

        $this->finalData['discount_amt'] = changePriceFormat($this->calcData['total_discount_without_tax']);
        $this->finalData['discount_inc_amt'] = changePriceFormat($this->calcData['total_discount_inc_tax']);

        $this->finalData['on_the_house_total'] = changePriceFormat($this->calcData['on_the_house_item_total']);

        $this->finalData['reservation_deducted_total'] = changePriceFormat($this->calcData['reservation_deducted_total']);

        $this->finalData['deposit_total'] = changePriceFormat($this->calcData['deposite_total']);
        $this->finalData['tip_amount'] = changePriceFormat($this->calcData['total_tip_amount']);

        $this->finalData['avg'] = changePriceFormat($this->calcData['final_avg']);

        foreach ($this->calcData['kioskTotal'] as $key => $value) {
            $this->finalData['kioskTotal'][$key] = changePriceFormat($value);
        }
    }

    protected function setGeneratorSpecificData()
    {
        if ($this->revenueType == RevenueTypes::MONTHLY) {
            $createdMonth = $this->year.'-'.$this->month;
            $this->finalData['start_date'] = Carbon::parse($createdMonth)->startOfMonth()->format('Y-m-d');
            $this->finalData['end_date'] = Carbon::now()->format('m') == $this->month ? Carbon::today()
                ->format('Y-m-d') : Carbon::parse($createdMonth)->endOfMonth()->format('Y-m-d');

            $order_detail = [];
            foreach ($this->calcData['dates'] as $date) {
                $order_detail[$date]['date'] = $date;
                $order_detail[$date]['coupon_price'] = changePriceFormat($this->calcData['coupon_used_prince_date'][$date]);
                $order_detail[$date]['tax1_amount'] = changePriceFormat($this->calcData['total_9_tax_date'][$date]);
                $order_detail[$date]['tax2_amount'] = changePriceFormat($this->calcData['total_21_tax_date'][$date]);

                $order_detail[$date]['total_turnover_with_tax'] = changePriceFormat($this->calcData['total_turn_over_with_tax_date'][$date]);
                $order_detail[$date]['total_turnover_without_tax'] = changePriceFormat($this->calcData['total_turn_over_without_tax_date'][$date]);
                $order_detail[$date]['total_tax_amount'] = changePriceFormat($this->calcData['total_tax_date'][$date]);

                $order_detail[$date]['total_discount'] = changePriceFormat($this->calcData['total_discount_inc_tax_date'][$date]);
//                $order_detail[$date]['total_discount_without_tax'] = changePriceFormat($this->calcData['total_discount_without_tax_date'][$date]);
            }

            $this->finalOrderDetail = $order_detail;
        }

        if ($this->revenueType == RevenueTypes::DAILY) {
            $date = Carbon::parse(request()->date)->format('d-m-Y');
            $this->finalData['start_date'] = $date.' 00:00';
            $this->finalData['end_date'] = $date.' 23:59';
            $this->finalData['original_total_amount'] = changePriceFormat($this->calcData['original_total_amount']);
            $this->finalData['total_0_without_tax_subtotal'] = changePriceFormat($this->calcData['total_0_without_tax_subtotal']);

            $this->finalData['total_9_without_tax_discount_subtotal'] = changePriceFormat($this->calcData['total_9_without_tax_discount_subtotal']);
            $this->finalData['total_21_without_tax_discount_subtotal'] = changePriceFormat($this->calcData['total_21_without_tax_discount_subtotal']);

            $this->finalData['total_9_inc_tax_without_discount_subtotal'] = changePriceFormat($this->calcData['total_9_inc_tax_without_discount_subtotal']);
            $this->finalData['total_21_inc_tax_without_discount_subtotal'] = changePriceFormat($this->calcData['total_21_inc_tax_without_discount_subtotal']);
        }
    }

    protected function setMainPrinter()
    {
        $this->finalJson['printername'] = $this->additionalSettings['default_printer_name'];
    }

    protected function setPrintTitles()
    {
        $title1 = __companionPrintTrans('general.day_revenue');
        $title2 = $this->store->store_name;
        $title3 = $this->store->address;
        $title4 = $this->store->store_phone;
        $title5 = $this->store->store_email;
        $title6 = __companionPrintTrans('general.print_date').': '.$this->finalData['current_date_time'];
        $title7 = __companionPrintTrans('general.from_date').': '.$this->finalData['start_date'];
        $title8 = __companionPrintTrans('general.to_date').': '.$this->finalData['end_date'];
        $this->finalJson['title1'] = $title1;
        $this->finalJson['title2'] = $title2;
        $this->finalJson['title3'] = $title3;
        $this->finalJson['title4'] = $title4;
        $this->finalJson['title5'] = $title5;
        $this->finalJson['title6'] = $title6;
        $this->finalJson['title7'] = $title7;
        $this->finalJson['title8'] = $title8;
    }

    protected function setSummaryTop()
    {

        // cash amount -------------------------------------------------------------------------------------------------
        $summaryTop = [];
        $summaryTop['title'] = __companionPrintTrans('general.cash_title');

        $detailJson['key'] = __companionPrintTrans('general.cash');
        $detailJson['value'] = '€'.($this->finalData['total_cash_amount']);
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.cash_difference');
        $detailJson['value'] = '€0,00';
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'line';
        $detailJson['value'] = '1';
        $summaryTop['details'][] = $detailJson;

        $totalJson['key1'] = __companionPrintTrans('general.total_cash_registered');
        $totalJson['value1'] = '€'.($this->finalData['total_cash_amount']);
        $summaryTop['total'][] = $totalJson;

        $this->finalJson['summarytop'][] = $summaryTop;

        // online/card payment -----------------------------------------------------------------------------------------
        $summaryTop = [];
        $summaryTop['title'] = __companionPrintTrans('general.wireless_payment');

        $detailJson['key'] = __companionPrintTrans('general.pin');
        $detailJson['value'] = '€'.($this->finalData['total_pin_amount']);
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.online_payment');
        $detailJson['value'] = '€'.($this->finalData['total_ideal_amount']);
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'line';
        $detailJson['value'] = '1';
        $summaryTop['details'][] = $detailJson;

        $totalJson['key1'] = __companionPrintTrans('general.digital_payment');
        $totalJson['value1'] = '€'.($this->finalData['total_pin_ideal_amount']);
        $summaryTop['total'][] = $totalJson;

        $totalJson['key1'] = __companionPrintTrans('general.total_registered');
        $totalJson['value1'] = '€'.($this->finalData['total_pin_ideal_cash_amount']);
        $summaryTop['total'][] = $totalJson;

        $this->finalJson['summarytop'][] = $summaryTop;

        // counts and other details ------------------------------------------------------------------------------------
        $summaryTop = [];
        $summaryTop['title'] = '';

        $detailJson['key'] = __companionPrintTrans('general.number_receipt');
        $detailJson['value'] = ''.$this->finalData['total_orders'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.number_of_cashdrawer_open');
        $detailJson['value'] = ''.$this->finalData['number_of_cashdrawer_open'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.total_cash');
        $detailJson['value'] = ''.$this->finalData['total_cash_orders'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.total_pin');
        $detailJson['value'] = ''.$this->finalData['total_pin_orders'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.gift_card_count').' ontvangen';
        $detailJson['value'] = ''.$this->finalData['gift_card_order_count'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.gift_card_count').' used';
        $detailJson['value'] = '€'.$this->finalData['coupon_price'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.total_online');
        $detailJson['value'] = ''.$this->finalData['total_ideal_orders'];
        $summaryTop['details'][] = $detailJson;

        if ($this->additionalSettings['third_party_revenue_status'] == 1) {
            $detailJson['key'] = 'Thuisbezorgd';
            $detailJson['value'] = ''.$this->finalData['thusibezorgd_orders'];
            $summaryTop['details'][] = $detailJson;

            $detailJson['key'] = 'Ubereats';
            $detailJson['value'] = ''.$this->finalData['ubereats_orders'];
            $summaryTop['details'][] = $detailJson;
        }

        $detailJson['key'] = __companionPrintTrans('general.total_products');
        $detailJson['value'] = ''.$this->finalData['products_count'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.average_spending');
        $detailJson['value'] = '€'.$this->finalData['avg'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.discount');
        $detailJson['value'] = '€'.$this->finalData['discount_inc_amt'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Plastic Bag Fee';
        $detailJson['value'] = '€'.$this->finalData['plastic_bag_fee'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Additional Fee';
        $detailJson['value'] = '€'.$this->finalData['additional_fee'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Delivery Fee';
        $detailJson['value'] = '€'.$this->finalData['delivery_fee'];
        $summaryTop['details'][] = $detailJson;

        if ($this->additionalSettings['on_the_house_status']) {
            $detailJson['key'] = 'On The House';
            $detailJson['value'] = '€'.$this->finalData['on_the_house_total'];
            $summaryTop['details'][] = $detailJson;
        }

        $detailJson['key'] = 'Deposit';
        $detailJson['value'] = '€'.$this->finalData['deposit_total'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Reservation Deducted Price';
        $detailJson['value'] = '€'.$this->finalData['reservation_deducted_total'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Reservation Refund';
        $detailJson['value'] = '€'.$this->finalData['reservation_refund_total'];
        $summaryTop['details'][] = $detailJson;

        $detailJson['key'] = 'Tip';
        $detailJson['value'] = '€'.$this->finalData['tip_amount'];
        $summaryTop['details'][] = $detailJson;

        $totalJson['key1'] = '';
        $totalJson['value1'] = '';
        $summaryTop['total'][] = $totalJson;

        $this->finalJson['summarytop'][] = $summaryTop;
    }

    protected function setMatrixDetails()
    {
        $this->finalJson['matrix'] = [
            [
                'mtitle1'  => __companionPrintTrans('general.percentage'),
                'mtitle2'  => __companionPrintTrans('general.revenue_ex_tax'),
                'mtitle3'  => __companionPrintTrans('general.tax_amount'),
                'mtitle4'  => __companionPrintTrans('general.revenue_with_tax'),
                'mdetails' => [
                    [
                        'value1' => '0%',
                        'value2' => '€'.($this->finalData['total_0_without_tax_subtotal']),
                        'value3' => '€0,00',
                        'value4' => '€'.($this->finalData['total_0_without_tax_subtotal']),
                    ],
                    [
                        'value1' => '9%',
                        'value2' => '€'.($this->finalData['total_9_without_tax_discount_subtotal']),
                        'value3' => '€'.($this->finalData['total_9_tax']),
                        'value4' => '€'.($this->finalData['total_9_inc_tax_without_discount_subtotal']),
                    ],
                    [
                        'value1' => '21%',
                        'value2' => '€'.($this->finalData['total_21_without_tax_discount_subtotal']),
                        'value3' => '€'.($this->finalData['total_21_tax']),
                        'value4' => '€'.($this->finalData['total_21_inc_tax_without_discount_subtotal']),
                    ],
                ],
            ],
        ];
    }

    protected function setSummaryBottom()
    {
        $summaryBottom = [];
        $summaryBottom['title'] = __companionPrintTrans('general.total_sales_per_channel');

        $detailJson['key'] = __companionPrintTrans('general.takeaway');
        $detailJson['value'] = '€'.($this->finalData['total_takeaway']);
        $summaryBottom['details'][] = $detailJson;

        foreach ($this->store->kioskDevices as $device) {
            $detailJson['key'] = (isset($device->name) && $device->name != null && $device->name != '') ? $device->name : 'kiosk';
            $detailJson['value'] = '€'.($this->finalData['kioskTotal'][$device->name]);
            $summaryBottom['details'][] = $detailJson;
        }

        $detailJson['key'] = __companionPrintTrans('general.dine_in_revenue');
        $detailJson['value'] = '€'.($this->finalData['total_dine_in']);
        $summaryBottom['details'][] = $detailJson;

        $detailJson['key'] = __companionPrintTrans('general.gift_card_revenue');
        $detailJson['value'] = '€'.($this->finalData['total_gift_card_amount']);
        $summaryBottom['details'][] = $detailJson;

        $detailJson['key'] = 'Reservation Deposited';
        $detailJson['value'] = '€'.($this->finalData['reservation_received_total']);
        $summaryBottom['details'][] = $detailJson;

//        $detailJson['key'] = 'Reservation refund';
//        $detailJson['value'] = '€'.($this->finalData['reservation_refund_total']);
//        $summaryBottom['details'][] = $detailJson;

        if ($this->additionalSettings['third_party_revenue_status'] == 1) {
            $detailJson['key'] = 'Thuisbezorgd';
            $detailJson['value'] = '€'.$this->finalData['thusibezorgd_amount'];
            $summaryBottom['details'][] = $detailJson;

            $detailJson['key'] = 'Ubereats';
            $detailJson['value'] = '€'.$this->finalData['ubereats_amount'];
            $summaryBottom['details'][] = $detailJson;
        }

        $detailJson['key'] = 'line';
        $detailJson['value'] = '1';
        $summaryBottom['details'][] = $detailJson;

        $totalJson['key1'] = __companionPrintTrans('general.total_sumup_from');
        $totalJson['value1'] = '€'.($this->finalData['final_total']);
        $summaryBottom['total'][] = $totalJson;

        $this->finalJson['summarybottom'][] = $summaryBottom;
    }

    protected function setFooter()
    {
        $this->finalJson['thankyounote'] = [];
        $this->finalJson['footertag'] = [
            __companionPrintTrans('general.end_report'),
        ];
    }
}
