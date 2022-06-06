<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Classes\ImageFilters;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionPrintTrans;
use function Weboccult\EatcardCompanion\Helpers\changePriceFormat;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generateQrCode;

/**
 * @description Stag 8
 * @mixin BaseGenerator
 */
trait Stage8PrepareFinalJson
{
    /**
     * @return void
     * set Main receipt Printer base on related settings
     */
    protected function setMainPrinter()
    {
        //no need to set printer name if setting is enabled
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
//        companionLogger('----Companion Print printername', $printer_name);
    }

    /**
     * @return void
     * set kitchen print related data in final print json
     */
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

    /**
     * @return void
     * set open cash drawer flag details
     */
    protected function setOpenCashDrawer()
    {
        if ($this->systemType == SystemTypes::POS && $this->printType == PrintTypes::DEFAULT && in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            $order = $this->order;
            if ($this->orderType == OrderTypes::SUB) {
                $order = $this->subOrder;
            }

            if (isset($order['method']) && isset($order['total_price']) && $order['method'] == 'cash') {
                $this->jsonFormatFullReceipt['opendrawer'] = (int) $order['total_price'] > 0 ? '1' : '0';
            }
        }
    }

    /**
     * @return void
     * set Main and Kitchen print header titles and related details
     */
    protected function setPrintTitles()
    {
        $title1 = '';
        $title2 = '';
        $title3 = '';
        $title4 = '';
        $title5 = '';
        $mainreceiptordernumber = '';
        $orderNo = '';
        $title6 = '';
        $titleTime = '';
        $pickupTime = '';
        $dynamicOrderNo = ! empty($this->advanceData['dynamicOrderNo']) ? ('#'.$this->advanceData['dynamicOrderNo']) : '';

        if ($this->orderType == OrderTypes::PAID) {
            if ($this->systemType == SystemTypes::KDS) {
                $title1 = '';
                $title2 = '';
                $title3 = '';
                $title4 = '';
                $title5 = $dynamicOrderNo;
                $title6 = date('Y-m-d').' om '.date('H:i');
                $titleTime = '';
                $pickupTime = '';
            } else {
                $title1 = 'Uw bestelling is '.__companionPrintTrans('general.'.($this->order['status'] ?? ''));
                $title2 = $this->order['full_name'] ?? '';
                $title3 = $this->order['contact_no'] ?? '';
                if ($this->additionalSettings['is_print_exclude_email'] == 0) {
                    $title4 = $this->order['email'] ?? '';
                }
                $title5 = '#'.($this->order['order_id'] ?? '');
                $title6 = __companionPrintTrans('general.'.($this->order['order_type'] ?? '')).' op '.($this->order['order_date'] ?? '').($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));
                //            $title6 = ($this->order['order_type'] ?? '').' op '.($this->order['order_date'] ?? '').
                //                            ($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));
                $titleTime = carbonFormatParse('d-m-Y H:i', ($this->order['paid_on'] ?? ''));
                $pickupTime = ($this->order['order_time']) ? ($this->order['is_asap'] ? 'ZSM' : $this->order['order_time']) : '';
            }

            // for reservation order need to add table name prefix before name | skip for dine-in guest orders (is_dine_in)
            if (! empty($this->advanceData['tableName']) && ! empty($this->reservation) && ! $this->additionalSettings['dinein_guest_order']) {
                $orderNo = 'Table '.$this->advanceData['tableName'];
                $orderNo .= $this->additionalSettings['show_main_order_number_in_print'] == 0 ? ' '.$dynamicOrderNo : '';
            } else {
                $orderNo = $dynamicOrderNo;
            }
        }

        if ($this->orderType == OrderTypes::RUNNING) {
            if ($this->printType == PrintTypes::PROFORMA) {
                $title1 = 'Proforma';
                $title2 = ($this->reservation['voornaam']) ? $this->reservation['voornaam'].' '.$this->reservation['achternaam'] : '';
                $title3 = $this->reservation['gsm_no'] ?? '';

                if ($this->additionalSettings['is_print_exclude_email'] == 0) {
                    $title4 = $this->reservation['email'] ?? '';
                }

                $orderNo = 'Table '.$this->advanceData['tableName'];
                $titleTime = '';

                if ($this->systemType == SystemTypes::KIOSKTICKETS) {
                    $title1 = 'Tickets';
                    $title2 = $this->reservation['voornaam'] ?? '';
                    $title3 = $this->reservation['gsm_no'] ?? '';
                    if ($this->additionalSettings['is_print_exclude_email'] == 0) {
                        $title4 = $this->reservation['email'] ?? '';
                    }
                    $title6 = date('Y-m-d').' om '.date('H:i');

                    $orderNo = $this->advanceData['tableName'];
                    $this->jsonFormatFullReceipt['titteTime'][0]['key2'] = 'Betaald op:';
                    $titleTime = Carbon::parse($this->reservation['paid_on'])->format('Y-m-d H:i') ?? '';
                }
            }

            //reservation order item set then it will be kitchen print of round order
            if (! empty($this->reservationOrderItems)) {
                $title5 = 'Table #'.($this->reservationOrderItems->table->name ?? '');
                $orderNo = $title5;
            }
        }

        if ($this->orderType == OrderTypes::SAVE) {
            $title2 = $this->saveOrder['name'] ?? '';
            $title3 = $this->saveOrder['phone'] ?? '';
            $title5 = isset($this->saveOrder['random_id']) && ! empty($this->saveOrder['random_id']) ? '#'.$this->saveOrder['random_id'] : '';
            $title6 = $this->saveOrder['order_time'] ?? '';
            $orderNo = '';

            if ($this->systemType == SystemTypes::KDS) {
                $title1 = '';
                $title2 = '';
                $title3 = '';
                $title4 = '';
                $title6 = date('Y-m-d').' om '.date('H:i');
                $orderNo = $title5;
                $pickupTime = '';
            }
        }

        if ($this->orderType == OrderTypes::SUB) {
            $title1 = 'Uw bestelling is '.__companionPrintTrans('general.'.($this->subOrder['status'] ?? ''));
            $title5 = '#'.($this->order['order_id'] ?? '');
            $titleTime = $this->subOrder['paid_on'] ?? '';
            $pickupTime = $this->order['order_time'] ?? '';
            // for reservation order need to add table name prefix before name
            if (! empty($this->advanceData['tableName']) && ! empty($this->reservation)) {
                $orderNo = 'Table '.$this->advanceData['tableName'];
                $orderNo .= $this->additionalSettings['show_main_order_number_in_print'] == 0 ? ' '.$dynamicOrderNo : '';
            } else {
                $orderNo = $dynamicOrderNo;
            }
        }

        if (! empty($titleTime)) {
            $this->jsonFormatFullReceipt['titteTime'][0]['value2'] = $titleTime;
        } else {
            unset($this->jsonFormatFullReceipt['titteTime']);
        }

        if ($this->additionalSettings['show_main_order_number_in_print'] == 1) {
            $mainreceiptordernumber = $title5;
        }

        $this->jsonFormatFullReceipt['title1'] = $title1;
        $this->jsonFormatFullReceipt['title2'] = $title2;
        $this->jsonFormatFullReceipt['title3'] = $title3;
        $this->jsonFormatFullReceipt['title4'] = $title4;
        $this->jsonFormatFullReceipt['title5'] = $title5;
        $this->jsonFormatFullReceipt['mainreceiptordernumber'] = $mainreceiptordernumber;
        $this->jsonFormatFullReceipt['ordernumber'] = $orderNo;
        $this->jsonFormatFullReceipt['title6'] = $title6;
        $this->jsonFormatFullReceipt['pickuptime'] = $pickupTime;
    }

    /**
     * @return void
     * Set user address details for delivery orders
     */
    protected function setDeliveryAddress()
    {
        $deliveryTitle = '';
        $deliveryAddress = '';
        $deliveryFontSize = 0;
        $kitchenDeliveryAddress = '';

        if ($this->orderType == OrderTypes::PAID) {
            if (isset($this->order['order_type']) && $this->order['order_type'] == 'delivery') {
                $deliveryTitle = __companionPrintTrans('general.'.$this->order['order_type']);
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

    /**
     * @return void
     * set store details print in footer of print
     */
    protected function setStoreAddress()
    {
        if (empty($this->store)) {
            return;
        }

        if ($this->skipMainPrint) {
            //skip for kitchen print
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
        $email = $this->store->store_email ?? '';
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
     * Set Store and Eatcard logo, based on related settings
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
            $eatcardLogo = config('eatcardCompanion.aws_url').'/assets/eatcard-logo-print.png';
        }

        $this->jsonFormatFullReceipt['logo'] = ! empty($logo) ? ImageFilters::applyFilter('StorePrintLogoImage', $logo) : '';
        $this->jsonFormatFullReceipt['eatcardlogo'] = $eatcardLogo;
        $this->jsonFormatFullReceipt['showstorename'] = $showStoreName;
        $this->jsonFormatFullReceipt['showeatcardname'] = $showEatcardName;
    }

    protected function setTicketsQR()
    {
        if ($this->systemType != SystemTypes::KIOSKTICKETS) {
            return;
        }

        $qrImage = generateQrCode($this->store, $this->reservation->reservation_id, 'RT', true);

        $this->jsonFormatFullReceipt['qrtext'] = $this->reservation->reservation_id;
        $this->jsonFormatFullReceipt['qrimage'] = $qrImage['aws_image'] ?? '';
    }

    /**
     * @return void
     * set other additional details related to his order type, print type and settings
     */
    protected function setOtherDetails()
    {
        $checkoutNo = '';
        $typeOrder = '';
        $orderType = '';
        $dateTime = '';
        $customerComments = '';
        $total = '0';
        $itemTitle = '';
        $tableName = '';

        $tableName = $this->advanceData['tableName'];

        if ($this->orderType == OrderTypes::PAID) {
            $checkoutNo = $this->order['checkout_no'] ?? '';

            $typeOrder = $this->additionalSettings['thirdPartyName'].__companionPrintTrans('general.'.($this->order['order_type'] ?? ''));

            if ($this->advanceData['is_paylater_order'] == 1) {
                $typeOrder .= ' (Paylater)';
            }

            $dateTime = ($this->order['order_date'] ?? '').($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));

            $customerComments = $this->order['comment'] ?? '';

            $total = $this->order['total_price'] ?? '0';

            //skip for dine-in guest orders
            if (! in_array($this->systemType, [SystemTypes::POS]) && ! $this->additionalSettings['dinein_guest_order']) {
                $orderType = ($this->order['dine_in_type']) ? __companionPrintTrans('general.'.$this->order['dine_in_type']) : '';
            }

            if ($this->systemType == SystemTypes::KDS) {
                $itemTitle = ! empty($this->advanceData['dynamicOrderNo']) ? ('#'.$this->advanceData['dynamicOrderNo']) : '';
                $dateTime = date('Y-m-d').' om '.date('H:i');
                $orderType = '';
            }

            //not for dine-in guest order
            if ($tableName != '' && $this->additionalSettings['show_main_order_number_in_print'] == 0 && ! $this->additionalSettings['dinein_guest_order']) {
                $tableName .= ! empty($this->advanceData['dynamicOrderNo']) ? (' #'.$this->advanceData['dynamicOrderNo']) : '';
            }
        }

        if ($this->orderType == OrderTypes::RUNNING) {
            if (! empty($this->reservationOrderItems)) {
                $itemTitle = 'Table #'.($this->reservationOrderItems->table->name ?? '');
            }

            $dateTime = date('Y-m-d').' om '.date('H:i');
            if ($this->systemType == SystemTypes::KDS) {
                $typeOrder = 'Dine-in';
            }

            if ($this->systemType == SystemTypes::KIOSKTICKETS) {
                $dateTime = ($this->reservation->getRawOriginal('res_date') ?? '').(' om '.($this->reservation['from_time'] ?? ''));
            }

            if ($this->printType == PrintTypes::PROFORMA && isset($this->total_price)) {
                $total = $this->total_price;
                $tableName = '';
            }
        }

        if ($this->orderType == OrderTypes::SAVE) {
            $dateTime = $this->saveOrder['order_time'] ?? '';

            if ($this->systemType == SystemTypes::KDS) {
                $dateTime = date('Y-m-d').' om '.date('H:i');
                $typeOrder = 'POS';
                $itemTitle = isset($this->saveOrder['random_id']) && ! empty($this->saveOrder['random_id']) ? '#'.$this->saveOrder['random_id'] : '';
                $tableName = $itemTitle;
            }

            if (isset($this->total_price)) {
                $total = $this->total_price;
            }
        }

        if ($this->orderType == OrderTypes::SUB) {
            $customerComments = $this->order['comment'] ?? '';
            $dateTime = ($this->order['order_date'] ?? '').($this->order['is_asap'] ? ' | ZSM' : ' om '.($this->order['order_time'] ?? ''));
            $total = $this->subOrder['total_price'] ?? '0';
            $orderType = ($this->order['dine_in_type']) ? __companionPrintTrans('general.'.$this->order['dine_in_type']) : '';
            $tableName = '';
        }

        if (! empty($typeOrder)) {
            $this->jsonFormatFullReceipt['typeorder'] = $typeOrder;
        } else {
            // need to unset remove white space in header for round order kitchen print
            unset($this->jsonFormatFullReceipt['typeorder']);
        }

        $this->jsonFormatFullReceipt['kioskname'] = $this->kiosk->name ?? '';
        $this->jsonFormatFullReceipt['tablename'] = $tableName;
        $this->jsonFormatFullReceipt['checkoutno'] = $checkoutNo;
        $this->jsonFormatFullReceipt['ordertype'] = $orderType;
        $this->jsonFormatFullReceipt['datetime'] = $dateTime;
        $this->jsonFormatFullReceipt['headertag'] = []; //not in use
        $this->jsonFormatFullReceipt['footertag'] = []; //not in use
        $this->jsonFormatFullReceipt['customercomments'] = $customerComments;
        $this->jsonFormatFullReceipt['customtext'] = $this->additionalSettings['print_custom_text'];
        $this->jsonFormatFullReceipt['fullreceipt'] = $this->additionalSettings['fullreceipt'];
        $this->jsonFormatFullReceipt['kitchenreceipt'] = $this->additionalSettings['kitchenreceipt'];
        $this->jsonFormatFullReceipt['itemsTitle'] = $itemTitle;
        $this->jsonFormatFullReceipt['Total'][0]['value1'] = changePriceFormat($total);
        $this->jsonFormatFullReceipt['thankyounote'][] = __companionPrintTrans('general.thank_you_line_2');
        $this->jsonFormatFullReceipt['categories_settings'] = $this->additionalSettings['categories_settings'];
        $this->jsonFormatFullReceipt['noofprints'] = $this->additionalSettings['no_of_prints'];
        $this->jsonFormatFullReceipt['totalfontsize'] = $this->additionalSettings['print_total_font_size'];
    }

    /**
     * @return void
     * set order items in final json
     */
    protected function setItems()
    {
        $this->jsonFormatFullReceipt['items'] = $this->jsonItems;
    }

    /**
     * @return void
     * set third party payment receipt details in final json
     */
    protected function setReceipt()
    {
        $this->jsonFormatFullReceipt['receipt'] = $this->jsonReceipt;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setPreSummary()
    {
        $this->jsonFormatFullReceipt['preSubtotalSummary'] = $this->jsonPreSummary;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setSubTotal()
    {
        $this->jsonFormatFullReceipt['subtotal'] = $this->jsonSubTotal;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setTaxDetail()
    {
        $this->jsonFormatFullReceipt['MiscellaneousSummary1'] = $this->jsonTaxDetail;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setGeneralComments()
    {
        $this->jsonFormatFullReceipt['miscellaneous'] = $this->jsonGeneralComments;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setSummary()
    {
        $this->jsonFormatFullReceipt['summary'] = $this->jsonSummary;
    }

    /**
     * @return void
     * set bill summary in final json
     */
    protected function setPaymentSummary()
    {
        $this->jsonFormatFullReceipt['summary4'] = $this->jsonPaymentSummary;
    }
}
