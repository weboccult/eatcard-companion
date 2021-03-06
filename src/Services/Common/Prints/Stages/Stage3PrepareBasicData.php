<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;
use function Weboccult\EatcardCompanion\Helpers\__companionPrintTrans;

/**
 * @description Stag 3
 */
trait Stage3PrepareBasicData
{
    protected function prepareDefaultValue()
    {

        // format of item
        $this->itemFormat = [
                'qty'                => '',
                'itemname'           => '',
                'itemaddons'         => [],
                'kitchenitemaddons'  => [],
                'printername'        => [],
                'labelprintname'     => [],
                'category'           => '',
                'kitchendescription' => '',
                'mainproductcomment' => '',
                'price'              => '',
                'original_price'     => 0,
                'on_the_house'       => 0,
                'void_id'            => 0,
            ];

        //format of full json
        $this->jsonFormatFullReceipt = [
                'printername'            => [],
                'kitchencomment'         => '',
                'kitchensizeformat'      => 1,
                'kitchenheaderspace'     => 0,
                'kitchenheaderformat'    => 0,
                'kitchensubheader'       => 0,
                'totalfontsize'       => 0,
                'printcategory'          => '0',
                'printproduct'           => '0',
                'doubleheight'           => '0',
                'doublewidth'            => '0',
                'productdoubleheight'    => '0',
                'SeparatorLength'        => '',
                'opendrawer'             => '0',
                'title1'                 => '',
                'title2'                 => '',
                'title3'                 => '',
                'title4'                 => '',
                'title5'                 => '#',
                'mainreceiptordernumber' => '',
                'ordernumber'            => '#',
                'title6'                 => '',
                'titteTime'              => [
                    [
                        'key2'   => 'Besteldatum:',
                        'value2' => '',
                    ],
                ],
                'deliverytitle'          => '',
                'deliveryaddress'        => '',
                'deliveryfontsize'       => 0,
                'kitchendeliveryaddress' => '',
                'address'                => '',
                'address1'               => '',
                'address2'               => '',
                'address3'               => '',
                'address4'               => '',
                'address5'               => '',
                'phone'                  => '',
                'email'                  => '',
                'zipcode'                => '',
                'websiteurl'             => '',
                'kvknumber'              => '',
                'btwnumber'              => '',
                'pickuptime'             => '',
                'logo'                   => '',
                'eatcardlogo'            => '',
                'showstorename'          => '',
                'showeatcardname'        => '',
                'kioskname'              => '',
                'tablename'              => '',
                'checkoutno'             => '',
                'ordertype'              => '',
                'typeorder'              => '',
                'datetime'               => '',
                'headertag'              => [],
                'customercomments'       => '',
                'customtext'             => '',
                'fullreceipt'            => '0',
                'kitchenreceipt'         => '1',
                'noofprints'             => '1',
                'itemsTitle'             => '',
                'items'                  => [],
                'preSubtotalSummary'     => [],
                'subtotal'               => [],
                'summary'                => [],
                'Total'                  => [
                    [
                        'key1'   => __companionPrintTrans('print.total'),
                        'value1' => '',
                    ],
                ],
                'summary4'               => [],
                'MiscellaneousSummary1'  => [],
                'miscellaneous'          => [],
                'receipt'                => [],
                'thankyounote'           => [],
                'footertag'              => [],
                'categories_settings'    => [],
            ];

        //additional settings fix formates
        $this->additionalSettings = [

            //protocol variables
            'request_type'                          => '',
            'current_device_id'                     => 0,

            //Order related settings
            'show_supplement_kitchen_name'          => false,

            //reservation settings
            'kitchen_comment' => '',
            'addon_print_categories' => [],
            'show_no_of_pieces' => false,
            'ayce_data' => [],
            'is_until' => false,

            //store settings
            'hide_free_product' => 0,
            'hide_void_product' => 0,
            'hide_onthehouse_product' => 0,
            'show_product_comment_in_main_receipt' => 0,
            'show_order_transaction_detail' => 0,
            'is_print_exclude_email'         => 0,
            'alternative_printer'         => '',
            'default_printer_name'         => '',
            'exclude_print_status'         => false,
            'exclude_print_from_main_print'         => '',
            'main_print_logo_hide'         => 0,
            'double_height'         => '0',
            'kitchenheaderspace'         => 0,
            'kitchenheaderformat'         => 0,
            'kitchensubheader'         => 0,
            'is_print_category'         => '0',
            'is_print_product'         => '0',
            'double_width'         => '0',
            'product_double_height'         => '0',
            'print_separator_length'         => '',
            'delivery_address_font_size'         => 0,
            'show_delivery_address_in_kitchen_receipt'         => 0,
            'print_custom_text'         => '',
            'kiosk_data' => [],
            'no_of_prints' => '1',
            'show_main_order_number_in_print' => 0,
            'print_total_font_size' => 0,

            //kiosk device settings
            'kioskname' => '',
            'cash_drawer_available' => 0,
            'kiosk_printer_name' => '',
            'is_print_cart_add' => 0,
            'is_print_split' => 0,

            //advance data
            'thirdPartyName' => '',
            'fullreceipt' => '0',
            'kitchenreceipt' => '1',
            'categories_settings' => [],
            'dinein_guest_order' => false,

        ];

        //format of advance data
        $this->advanceData = [
            'tableName' => '',
            'dynamicOrderNo' => '',
            'show_discount_note' => '',

            //PDF, HTML ViewName
            'viewPath' => '',

        ];
    }

    /**
     * @return void
     *  use for store protocol print payload data
     */
    protected function preparePayloadData()
    {
        $globalOrderId = $this->payload['order_id'];
        if (strpos($globalOrderId, 'pos') !== false) {
            $this->additionalSettings['exclude_print_status'] = true;
            $order_id = explode('pos', $globalOrderId);
            $this->globalOrderId = $order_id[0];
        } else {
            $this->globalOrderId = $globalOrderId;
        }

        if (empty($this->globalOrderId)) {
            throw new OrderIdEmptyException();
        }

        if (isset($this->payload['item_id']) && ! empty($this->payload['item_id'])) {
            $this->globalItemId = $this->payload['item_id'];
        }

        if (isset($this->payload['kds_user_id']) && ! empty($this->payload['kds_user_id'])) {
            $this->kdsUserId = $this->payload['kds_user_id'];
        }

        //set print type main print for PDF and HTML
        if (in_array($this->printMethod, [PrintMethod::PDF, PrintMethod::HTML])) {
            $this->printType = PrintTypes::MAIN;
        }

        $this->takeawayEmailType = $this->payload['takeawayEmailType'] ?? '';
    }

    /**
     * @return void
     * set paid order data
     */
    protected function prepareOrderData()
    {
        if ($this->orderType == OrderTypes::PAID) {
            $this->orderId = $this->globalOrderId;
            $this->orderItemId = ! empty($this->globalItemId) ? $this->globalItemId : 0;
        }
    }

    /**
     * @return void
     * set reservation data
     */
    protected function prepareReservationData()
    {
        if ($this->orderType == OrderTypes::RUNNING && $this->printType == PrintTypes::PROFORMA) {
            $this->reservationId = $this->globalOrderId;
        }
    }

    /**
     * @return void
     * set reservation order item data related to KDS related print.
     */
    protected function prepareReservationOrderItemData()
    {
        if ($this->orderType == OrderTypes::RUNNING && in_array($this->printType, [PrintTypes::DEFAULT,
                                                                                  PrintTypes::KITCHEN_LABEL,
                                                                                  PrintTypes::KITCHEN, PrintTypes::LABEL, ])) {
            $this->reservationOrderItemId = $this->globalOrderId;
            $this->reservationOrderItemCartId = ! empty($this->globalItemId) ? $this->globalItemId : '';
        }
    }

    /**
     * @return void
     * set sub order data
     */
    protected function prepareSubOrderData()
    {
        if ($this->orderType == OrderTypes::SUB) {
            $this->subOrderId = $this->globalOrderId;
        }
    }

    /**
     * @return void
     * set save order data
     */
    protected function prepareSaveOrderData()
    {
        if ($this->orderType == OrderTypes::SAVE) {
            $this->saveOrderId = $this->globalOrderId;
            $this->saveOrderItemCartId = ! empty($this->globalItemId) ? $this->globalItemId : 0;
        }
    }

    /**
     * @return void
     * set global device id from protocol payload
     */
    protected function prepareDeviceId()
    {
        if (! empty($this->payloadRequestDetails)) {
            $this->additionalSettings['current_device_id'] = (int) ($this->payloadRequestDetails['deviceId'] ?? 0);
        }
    }
}
