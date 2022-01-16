<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Classes\ImageFilters;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 8
 * @mixin BaseGenerator
 */
trait Stage8PrepareFinalJson
{
    protected function setMainPrinter()
    {
        //no need to set printer name if setting is enable
        if ($this->skipMainPrint) {
            return;
        }

        $printer_name = [];
        //set printer as per system setting
        if ($this->systemType == SystemTypes::KIOSK && ! empty($this->additionalSettings['kiosk_printer_name'])) {
            $printer_name[] = $this->additionalSettings['kiosk_printer_name'];
        } elseif ($this->systemType == SystemTypes::POS && ! empty($this->additionalSettings['kiosk_printer_name'])) {
            $printer_name[] = $this->additionalSettings['kiosk_printer_name'];
        } elseif ($this->printType == PrintTypes::MAIN && ! empty($this->additionalSettings['kiosk_printer_name'])) {
            $printer_name[] = $this->additionalSettings['kiosk_printer_name'];
        }

        // set default print if printer not set yet
        if ($this->printMethod == PrintMethod::PROTOCOL && $this->systemType == SystemTypes::KIOSK && empty($printer_name)) {
            //not set default printer, skip for protocol print of kiosk
            $printer_name = [];
        } elseif (empty($printer_name) && ! empty($this->additionalSettings['default_printer_name'])) {
            $printer_name[] = $this->additionalSettings['default_printer_name'];
        }

        //set alternate printer if added in setting
        if (! empty($this->additionalSettings['alternative_printer'])) {
            $printer_name[] = $this->additionalSettings['alternative_printer'];
        }

        //not know why override it
        if (isset($this->payload['printer_name']) && ! empty($this->payload['printer_name'])) {
            $printer_name = [$this->payload['printer_name']];
        }

        $this->jsonFormatFullReceipt['printername'] = $printer_name;
        companionLogger('--1. printername', $printer_name);
    }

    protected function setKitchenRelatedFields()
    {
        $this->jsonFormatFullReceipt['kitchencomment'] = $this->additionalSettings['kitchen_comment'];

        $this->jsonFormatFullReceipt['kitchensizeformat'] = 1;

        $this->jsonFormatFullReceipt['kitchenheaderspace'] = $this->additionalSettings['kitchenheaderspace'];

        $this->jsonFormatFullReceipt['kitchenheaderformat'] = $this->additionalSettings['kitchenheaderformat'];

        $this->jsonFormatFullReceipt['kitchensubheader'] = $this->additionalSettings['kitchensubheader'];

        $this->jsonFormatFullReceipt['printcategory'] = $this->additionalSettings['is_print_category'];

        $this->jsonFormatFullReceipt['printproduct'] = $this->additionalSettings['is_print_product'];

        $this->jsonFormatFullReceipt['doubleheight'] = $this->additionalSettings['double_height'];

        $this->jsonFormatFullReceipt['doublewidth'] = $this->additionalSettings['double_width'];

        $this->jsonFormatFullReceipt['productdoubleheight'] = $this->additionalSettings['product_double_height'];

        $this->jsonFormatFullReceipt['SeparatorLength'] = $this->additionalSettings['print_separator_length'];
    }

    protected function setOpenCashDrawer()
    {
        if ($this->systemType == SystemTypes::POS && $this->printType == PrintTypes::DEFAULT && in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            if (isset($this->order['method']) && isset($this->order['total_price']) && $this->order['method'] == 'cash') {
                $this->jsonFormatFullReceipt['opendrawer'] = (int) $this->order['total_price'] > 0 ? '1' : '0';
            }
        }
    }

    protected function setPrintTitles()
    {
        $title1 = '';
        $title2 = '';
        $title3 = '';
        $title4 = '';
        $title5 = '';
        $orderNo = '';
        $title6 = '';
        $titleTime = '';
        $pickupTime = '';

        if ($this->orderType == OrderTypes::PAID) {
            $title1 = 'Uw bestelling is '.__('messages.'.($this->order['status'] ?? ''));
//            $title1 = 'Uw bestelling is '.($this->order['status'] ?? '');

            $title2 = $this->order['full_name'] ?? '';
            $title3 = $this->order['contact_no'] ?? '';

            if ($this->additionalSettings['is_print_exclude_email'] == 0) {
                $title4 = $this->order['email'] ?? '';
            }

            $title5 = '#'.($this->order['order_id'] ?? '');

            if (! empty($this->advanceData['tableName'])) {
                $orderNo = 'Table '.$this->advanceData['tableName'];
            }

            if (! empty($this->advanceData['dynamicOrderNo'])) {
                $orderNo = '#'.$this->advanceData['dynamicOrderNo'];
            }

            $title6 = __('messages.'.($this->order['order_type'] ?? '')).' op '.($this->order['order_date'] ?? '').
                            ($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));
//            $title6 = ($this->order['order_type'] ?? '').' op '.($this->order['order_date'] ?? '').
//                            ($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));

            $titleTime = $this->order['paid_on'] ?? '';

            $pickupTime = ($this->order['order_time']) ? ($this->order['is_asap'] ? 'ZSM' : $this->order['order_time']) : '';
        }

        $this->jsonFormatFullReceipt['title1'] = $title1;
        $this->jsonFormatFullReceipt['title2'] = $title2;
        $this->jsonFormatFullReceipt['title3'] = $title3;
        $this->jsonFormatFullReceipt['title4'] = $title4;
        $this->jsonFormatFullReceipt['title5'] = $title5;
        $this->jsonFormatFullReceipt['ordernumber'] = $orderNo;
        $this->jsonFormatFullReceipt['title6'] = $title6;
        $this->jsonFormatFullReceipt['titteTime'][0]['value2'] = $titleTime;
        $this->jsonFormatFullReceipt['pickuptime'] = $pickupTime;
    }

    protected function setDeliveryAddress()
    {
        $deliveryTitle = '';
        $deliveryAddress = '';
        $deliveryFontSize = 0;
        $kitchenDeliveryAddress = '';

        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['order_type']) && $this->order['order_type'] == 'delivery') {
                $deliveryTitle = __('messages.'.$this->order['order_type']);
//                $deliveryTitle = $this->order['order_type'] ?? '';
                $deliveryAddress = $this->order['delivery_address'] ?? '';
                $deliveryFontSize = $this->additionalSettings['delivery_address_font_size'];
            }

            if ($this->additionalSettings['show_delivery_address_in_kitchen_receipt'] == 1) {
                $kitchenDeliveryAddress = $this->order['delivery_address'] ?? '';
            }
        }

        $this->jsonFormatFullReceipt['deliverytitle'] = $deliveryTitle;
        $this->jsonFormatFullReceipt['deliveryaddress'] = $deliveryAddress;
        $this->jsonFormatFullReceipt['deliveryfontsize'] = $deliveryFontSize;
        $this->jsonFormatFullReceipt['kitchendeliveryaddress'] = $kitchenDeliveryAddress;
    }

    protected function setStoreAddress()
    {
        if (empty($this->store)) {
            return;
        }

        $address1 = '';
        $address2 = '';
        $address3 = '';
        $address4 = '';
        $address5 = '';

        $addressArray = explode(',', ($this->store->address ?? ''));
        $address = $this->store->company_name ?? '';
        if (! empty($addressArray)) {
            $address1 = trim($addressArray[0] ?? '');
            $address2 = $addressArray[1] ? ($this->store->zipcode ? $this->store->zipcode.', ' : '').trim($addressArray[1]) : '';
            $address3 = trim($addressArray[2] ?? '');
            $address4 = trim($addressArray[3] ?? '');
            $address5 = trim($addressArray[4] ?? '');
        }

        $phone = $this->store->store_phone ?? '';
        $email = $this->store->store_email && ! $this->additionalSettings['is_print_exclude_email'] ? $this->store->store_email : '';
        $zipcode = $this->store->zipcode ?? '';
        $websiteurl = $this->store->website_url ?? '';
        $kvknumber = $this->store->kvk_number ? 'KVK-'.$this->store->kvk_number : '';
        $btwnumber = $this->store->btw_number ? 'BTW-'.$this->store->btw_number : '';

        $this->jsonFormatFullReceipt['address'] = $address;
        $this->jsonFormatFullReceipt['address1'] = $address1;
        $this->jsonFormatFullReceipt['address2'] = $address2;
        $this->jsonFormatFullReceipt['address3'] = $address3;
        $this->jsonFormatFullReceipt['address4'] = $address4;
        $this->jsonFormatFullReceipt['address5'] = $address5;
        $this->jsonFormatFullReceipt['phone'] = $phone;
        $this->jsonFormatFullReceipt['email'] = $email;
        $this->jsonFormatFullReceipt['zipcode'] = $zipcode;
        $this->jsonFormatFullReceipt['websiteurl'] = $websiteurl;
        $this->jsonFormatFullReceipt['kvknumber'] = $kvknumber;
        $this->jsonFormatFullReceipt['btwnumber'] = $btwnumber;
    }

    /**
     * @throws \Exception
     */
    protected function setLogo()
    {
        $logo = '';
        $eatcardLogo = '';
        $showStoreName = '';
        $showEatcardName = '';

        if ($this->additionalSettings['main_print_logo_hide'] == 1) {
            $showStoreName = $this->store->company_name ?? '';
            $showEatcardName = 'Eatcard';
        } else {
            $logo = isset($this->additionalSettings['kiosk_data']['kiosk_logo']) && ! empty($this->additionalSettings['kiosk_data']['kiosk_logo'])
                    ? $this->additionalSettings['kiosk_data']['kiosk_logo'] : ($this->store->page_logo ?? '');
            $eatcardLogo = config('eatcardCompanion.aws_url').'assets/eatcard-logo-print.png';
        }

        $this->jsonFormatFullReceipt['logo'] = ! empty($logo) ? ImageFilters::applyFilter('StorePrintLogoImage', $logo) : '';
        $this->jsonFormatFullReceipt['eatcardlogo'] = $eatcardLogo;
        $this->jsonFormatFullReceipt['showstorename'] = $showStoreName;
        $this->jsonFormatFullReceipt['showeatcardname'] = $showEatcardName;
    }

    protected function setOtherDetails()
    {
        $checkoutNo = '';
        $typeOrder = '';
        $orderType = '';
        $dateTime = '';
        $customerComments = '';
        $total = '0,00';

        if ($this->orderType == OrderTypes::PAID) {
            $checkoutNo = $this->order['checkout_no'] ?? '';

            $typeOrder = $this->additionalSettings['thirdPartyName'].__('messages.'.($this->order['order_type'] ?? ''));
//            $typeOrder = $this->additionalSettings['thirdPartyName'] . ($this->order['order_type'] ?? '');
            $dateTime = ($this->order['order_date'] ?? '').($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));

            $customerComments = $this->order['comment'] ?? '';

            $total = ''.changePriceFormat($this->order['total_price'] ?? '0');

            if (! in_array($this->systemType, [SystemTypes::POS])) {
                $orderType = ($this->order['dine_in_type']) ? __('messages.'.$this->order['dine_in_type']) : '';
            }
        }

        $this->jsonFormatFullReceipt['kioskname'] = $this->kiosk->name ?? '';
        $this->jsonFormatFullReceipt['tablename'] = $this->advanceData['tableName'];
        $this->jsonFormatFullReceipt['checkoutno'] = $checkoutNo;
        $this->jsonFormatFullReceipt['ordertype'] = $orderType;
        $this->jsonFormatFullReceipt['typeorder'] = $typeOrder;
        $this->jsonFormatFullReceipt['datetime'] = $dateTime;
        $this->jsonFormatFullReceipt['headertag'] = []; //not in use
        $this->jsonFormatFullReceipt['footertag'] = []; //not in use
        $this->jsonFormatFullReceipt['customercomments'] = $customerComments;
        $this->jsonFormatFullReceipt['customtext'] = $this->additionalSettings['print_custom_text'];
        $this->jsonFormatFullReceipt['fullreceipt'] = $this->additionalSettings['fullreceipt'];
        $this->jsonFormatFullReceipt['kitchenreceipt'] = $this->additionalSettings['kitchenreceipt'];
        $this->jsonFormatFullReceipt['itemsTitle'] = ''; //not in use
        $this->jsonFormatFullReceipt['Total'][0]['value1'] = $total;
        $this->jsonFormatFullReceipt['thankyounote'][] = __('messages.thank_you_line_2');
        $this->jsonFormatFullReceipt['categories_settings'] = $this->additionalSettings['categories_settings'];
        $this->jsonFormatFullReceipt['noofprints'] = $this->additionalSettings['no_of_prints'];
    }

    protected function setItems()
    {
        $this->jsonFormatFullReceipt['items'] = $this->jsonItems;
    }

    protected function setReceipt()
    {
        $this->jsonFormatFullReceipt['receipt'] = $this->jsonReceipt;
    }

    protected function setSummary()
    {
        $this->jsonFormatFullReceipt['summary'] = $this->jsonSummary;
    }
}
