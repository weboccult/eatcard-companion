<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;

/**
 * @description Stag 5
 * @mixin BaseGenerator
 */
trait Stage5EnableSettings
{
    protected function enableStoreSettings()
    {
        //return if store setting is not set
        if (! isset($this->store->storeSetting)) {
            return;
        }

        $store = $this->store;

        $this->additionalSettings['show_supplement_kitchen_name'] = isset($store->show_supplement_kitchen_name) && $store->show_supplement_kitchen_name == 1;
        $this->additionalSettings['show_order_transaction_detail'] = $store->show_order_transaction_detail ?? 0;

        // 1=hide and 0=show for free, vod, onthehouse product
        if (isset($this->order['order_type']) && $this->order['order_type'] == 'all_you_eat') {
            $this->additionalSettings['hide_free_product'] = $store->storeSetting->show_free_print_item ?? 0;
        }

        $this->additionalSettings['hide_void_product'] = $store->storeSetting->show_void_print_item ?? 0;
        $this->additionalSettings['hide_onthehouse_product'] = $store->storeSetting->show_on_the_house_print_item ?? 0;
        $this->additionalSettings['show_product_comment_in_main_receipt'] = $store->storeSetting->show_product_comment_in_main_receipt ?? 0;
        $this->additionalSettings['is_print_exclude_email'] = $store->storeSetting->is_print_exclude_email ?? 0;
        $this->additionalSettings['alternative_printer'] = $store->storeSetting->alternative_printer ?? '';
        $this->additionalSettings['default_printer_name'] = $store->storeSetting->default_printer_name ?? '';
        $this->additionalSettings['exclude_print_from_main_print'] = $store->storeSetting->exclude_print_from_main_print ?? '';
        $this->additionalSettings['main_print_logo_hide'] = $store->storeSetting->main_print_logo_hide ?? 0;
        $this->additionalSettings['kiosk_data'] = $this->store->kiosk_data ? json_decode($this->store->kiosk_data, true) : [];

        $this->additionalSettings['double_height'] = ''.($store->storeSetting->double_height ?? '0');
//        if ($this->orderType != OrderTypes::SAVE) {
//        } else {
//            $this->additionalSettings['double_height'] = '0';
//        }

        if ($this->systemType == SystemTypes::KIOSK) {
            $this->additionalSettings['print_separator_length'] = ''.(isset($store->storeSetting->kiosk_separator_length) && ! empty($store->storeSetting->kiosk_separator_length) ? $store->storeSetting->kiosk_separator_length : ($store->storeSetting->print_separator_length ?? ''));
        } else {
            $this->additionalSettings['print_separator_length'] = ''.($store->storeSetting->print_separator_length ?? '');
        }

        $this->additionalSettings['kitchenheaderspace'] = (int) ($store->storeSetting->kitchenheaderspace ?? 0);
        $this->additionalSettings['kitchenheaderformat'] = (int) ($store->storeSetting->kitchenheaderformat ?? 0);
        $this->additionalSettings['kitchensubheader'] = (int) ($store->storeSetting->kitchensubheader ?? 0);
        $this->additionalSettings['is_print_category'] = ''.($store->storeSetting->is_print_category ?? '0');
        $this->additionalSettings['is_print_product'] = ''.($store->storeSetting->is_print_product ?? '0');
        $this->additionalSettings['double_width'] = ''.($store->storeSetting->double_width ?? '0');
        $this->additionalSettings['product_double_height'] = ''.($store->storeSetting->product_double_height ?? '0');
        $this->additionalSettings['delivery_address_font_size'] = (int) ($store->storeSetting->delivery_address_font_size ?? 0);
        $this->additionalSettings['show_delivery_address_in_kitchen_receipt'] = (int) ($store->storeSetting->show_delivery_address_in_kitchen_receipt ?? 0);
        $this->additionalSettings['print_custom_text'] = $store->storeSetting->print_custom_text ?? '';
        $this->additionalSettings['no_of_prints'] = ''.($store->storeSetting->no_of_prints ?? '1');
    }

    protected function enableStoreTakeawaySetting()
    {
        //return if store setting is not set
        if (! isset($this->store->takeawaySetting)) {
            return;
        }

        $this->additionalSettings['print_dynamic_order_no'] = $this->store->takeawaySetting->print_dynamic_order_no ?? 0;
    }

    protected function enableDeviceSettings()
    {
        //return if kiosk setting is not set
        if (! isset($this->kiosk->settings)) {
            return;
        }

        $kiosk = $this->kiosk;
        $this->additionalSettings['cash_drawer_available'] = (int) ($kiosk->settings->cash_drawer_available ?? 0);
        $this->additionalSettings['kioskname'] = $kiosk->kioskname ?? '';
        $this->additionalSettings['kiosk_printer_name'] = $kiosk->printer_name ?? '';
        $this->additionalSettings['is_print_cart_add'] = $kiosk->settings->is_print_cart_add ?? 0;

        if ($this->systemType == SystemTypes::POS) {
            $this->additionalSettings['addon_print_categories'] = isset($kiosk->settings->add_print_categories) && ! empty($kiosk->settings->add_print_categories) ? json_decode($kiosk->settings->add_print_categories, true) : [];
        }
    }

    protected function enableStoreReservationSettings()
    {
        //return if reservation data is empty
        if (empty($this->reservation)) {
            return;
        }

        $reservation = $this->reservation;

        $this->additionalSettings['kitchen_comment'] = $reservation->kitchen_comment ?? '';
        $this->additionalSettings['show_no_of_pieces'] = isset($reservation->reservation_type) && $reservation->reservation_type == 'cart';
        $this->additionalSettings['ayce_data'] = isset($reservation->all_you_eat_data) && ! empty($reservation->all_you_eat_data) ? json_decode($reservation->all_you_eat_data, true) : [];
        $this->additionalSettings['dinein_guest_order'] = isset($this->reservation->is_dine_in) && $this->reservation->is_dine_in == 1;
        $this->additionalSettings['is_until'] = isset($this->reservation->is_until) && $this->reservation->is_until == 1;
    }

    protected function enableGlobalSettings()
    {

        //skip main print for kitchen related prints
        if (in_array($this->printType, [PrintTypes::KITCHEN, PrintTypes::LABEL, PrintTypes::KITCHEN_LABEL])) {
            $this->skipMainPrint = true;
        }

        //skip for round order print
        if (! $this->skipMainPrint && $this->orderType == OrderTypes::RUNNING && $this->printType == PrintTypes::DEFAULT) {
            $this->skipMainPrint = true;
        }

        if ($this->systemType == SystemTypes::KDS) {
            $this->skipMainPrint = true;
        }
    }
}
