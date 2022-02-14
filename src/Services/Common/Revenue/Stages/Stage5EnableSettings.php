<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;

/**
 * @description Stag 5
 * @mixin BaseGenerator
 */
trait Stage5EnableSettings
{
    /**
     * @return void
     * set all settings related to store
     */
    protected function enableStoreSettings()
    {
        //return if store setting is not set
        if (! isset($this->store->storeSetting)) {
            return;
        }

        $store = $this->store;
        $this->additionalSettings['third_party_revenue_status'] = $store->storeSetting->third_party_revenue_status ?? 0;
        $this->additionalSettings['default_printer_name'] = $store->storeSetting->default_printer_name ?? '';
    }

    protected function enableDeviceSettings()
    {
        //return if kiosk setting is not set
        if (! isset($this->store->kioskDevices)) {
            return;
        }

        if (empty($this->store->kioskDevices)) {
            return;
        }

        foreach ($this->store->kioskDevices as $kiosk_device) {
            $this->additionalSettings['kiosk_device_id_array'][] = $kiosk_device->id;
            $this->calcData['kioskTotal'][$kiosk_device->id] = 0;
            $this->calcData['kioskTotal'][$kiosk_device->name] = 0;
            $this->calcData['store_device_amount'][$kiosk_device->name] = 0;
        }
    }

    protected function enableStorePosSettings()
    {
        //return if store setting is not set
        if (! isset($this->store->storePosSetting)) {
            return;
        }
        $store = $this->store;

        foreach ($store->storePosSetting as $on_the_house) {
            if (! is_null($on_the_house->pos_horizontal_bar)) {
                if (strpos($on_the_house->pos_horizontal_bar, 'On The House')) {
                    $this->additionalSettings['on_the_house_status'] = true;
                    break;
                }
            }
        }
    }

    protected function enableSearchDates()
    {

        //set start and end date
        if ($this->revenueType == RevenueTypes::DAILY) {
            $this->startDate = $this->date;
            $this->endDate = $this->date;
            $this->setDateinCalcArray($this->date);
        } elseif ($this->revenueType == RevenueTypes::MONTHLY) {
            $createdMonth = $this->year.'-'.$this->month;
            $this->startDate = Carbon::parse($createdMonth)->startOfMonth();
            $this->endDate = (Carbon::now()->format('m') == $this->month && Carbon::now()->format('Y') == $this->year) ?
               Carbon::today() : Carbon::parse($createdMonth)->endOfMonth();

            $start = $this->startDate;
            while ($this->startDate->lte($this->endDate)) {
                $this->setDateinCalcArray($start->copy()->format('Y-m-d'));
                $start->addDay();
            }

            //set future date related setting
            $end_date = new Carbon('last day of this month');
            $current_date = Carbon::now();
            while ($current_date->lte($end_date)) {
                $current_date->addDays();
                $this->additionalSettings['future_date_arr'] = $current_date->copy()->format('Y-m-d');
                $this->additionalSettings['future_date'][$current_date->copy()->format('Y-m-d')] = 0;
            }
        }
    }

    /**
     * @return void
     * set some global settings for skip prints based on there Print type
     */
    protected function enableGlobalSettings()
    {
        if ($this->revenueType == RevenueTypes::DAILY && in_array($this->revenueMethod, [PrintMethod::PROTOCOL, PrintMethod::SQS])) {
            $this->additionalSettings['is_Print'] = true;
        }

        if ($this->revenueMethod == PrintMethod::PDF) {
            $this->additionalSettings['is_PDF'] = true;
        }
    }

    protected function setDateinCalcArray($date)
    {
        $this->calcData['dates'][] = $date;
        $this->calcData['plastic_bag_fee_total_date'][$date] = 0;
        $this->calcData['delivery_fee_total_date'][$date] = 0;
//        $this->calcData['additional_fee_total_date'][$date] = 0;
//        $this->calcData['deposite_total_date'][$date] = 0;
        $this->calcData['reservation_received_total_date'][$date] = 0;
        $this->calcData['reservation_deducted_total_date'][$date] = 0;
        $this->calcData['total_third_party_total_date'][$date] = 0;
        $this->calcData['total_amount_inc_tax_date'][$date] = 0;
        $this->calcData['total_amount_without_tax_date'][$date] = 0;
        $this->calcData['total_9_without_tax_subtotal_date'][$date] = 0;
        $this->calcData['total_21_without_tax_subtotal_date'][$date] = 0;
        $this->calcData['total_9_tax_date'][$date] = 0;
        $this->calcData['total_21_tax_date'][$date] = 0;
        $this->calcData['total_tax_date'][$date] = 0;
        $this->calcData['total_discount_inc_tax_date'][$date] = 0;
        $this->calcData['total_discount_without_tax_date'][$date] = 0;
        $this->calcData['total_9_discount_without_tax_date'][$date] = 0;
        $this->calcData['total_21_discount_without_tax_date'][$date] = 0;
        $this->calcData['coupon_used_prince_date'][$date] = 0;
        $this->calcData['total_gift_card_count_date'][$date] = 0;
        $this->calcData['total_gift_card_amount_date'][$date] = 0;
        $this->calcData['total_turn_over_with_tax_date'][$date] = 0;
        $this->calcData['total_turn_over_without_tax_date'][$date] = 0;
    }
}
