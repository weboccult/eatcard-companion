<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints;

use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage4BasicDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage5EnableSettings;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage6DatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage7PrepareAdvanceData;
use Weboccult\EatcardCompanion\Services\Common\Prints\Stages\Stage8PrepareFinalJson;
use Weboccult\EatcardCompanion\Services\Common\Prints\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Services\Common\Prints\Traits\MagicAccessors;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @mixin MagicAccessors
 * @mixin AttributeHelpers
 */
abstract class BaseGenerator implements BaseGeneratorContract
{
    use MagicAccessors;
    use AttributeHelpers;
    use Stage1PrepareValidationRules;
    use Stage2ValidateValidations;
    use Stage3PrepareBasicData;
    use Stage4BasicDatabaseInteraction;
    use Stage5EnableSettings;
    use Stage6DatabaseInteraction;
    use Stage7PrepareAdvanceData;
    use Stage8PrepareFinalJson;

    protected array $itemFormat = [
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

    protected array $jsonFormatFullReceipt = [
        'printername'            => [],
        'kitchencomment'         => '',
        'kitchensizeformat'      => 1,
        'kitchenheaderspace'     => 0,
        'kitchenheaderformat'    => 0,
        'kitchensubheader'       => 0,
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
        'itemsTitle'             => '',
        'items'                  => [],
        'summary'                => [],
        'Total'                  => [
            [
                'key1'   => 'Totaal',
                'value1' => '',
            ],
        ],
        'receipt'                => [],
        'thankyounote'           => [],
        'footertag'              => [],
        'categories_settings'    => [],
    ];

    protected array $jsonFormatKitchen = [
        'printername'         => [],
        'kitchencomment'      => '',
        'kitchensizeformat'   => 1,
        'kitchenheaderspace'  => 0,
        'kitchenheaderformat' => 0,
        'kitchensubheader'    => 0,
        'printcategory'       => '0',
        'printproduct'        => '0',
        'doubleheight'        => '0',
        'doublewidth'         => '0',
        'productdoubleheight' => '0',
        'SeparatorLength'     => '64',
        'title1'              => '',
        'title2'              => '',
        'title3'              => '',
        'title4'              => '',
        'title5'              => '',
        'ordernumber'         => '',
        'title6'              => '',
        'titteTime'           => [
            [
                'key2'   => 'Besteldatum:',
                'value2' => '',
            ],
        ],
        'deliverytitle'       => '',
        'deliveryaddress'     => '',
        'address'             => '',
        'address1'            => '',
        'address2'            => '',
        'address3'            => '',
        'address4'            => '',
        'address5'            => '',
        'zipcode'             => '',
        'phone'               => '',
        'email'               => '',
        'websiteurl'          => '',
        'kvknumber'           => '',
        'btwnumber'           => '',
        'pickuptime'          => '',
        'logo'                => '',
        'eatcardlogo'         => '',
        'showstorename'       => '',
        'showeatcardname'     => '',
        'kioskname'           => '',
        'tablename'           => '',
        'checkoutno'          => '',
        'ordertype'           => '',
        'typeorder'           => '',
        'datetime'            => '',
        'headertag'           => [],
        'customercomments'    => '',
        'customtext'          => '',
        'fullreceipt'         => 0,
        'kitchenreceipt'      => '1',
        'itemsTitle'          => '',
        'items'               => [],
        'summary'             => [],
        'Total'               => [
            [
                'key1'   => 'Totaal',
                'value1' => '',
            ],
        ],
        'receipt'             => [],
        'thankyounote'        => [],
        'footertag'           => [
            '',
        ],
        'categories_settings' => [],
    ];

    protected array $finalPrintFormat = [];

    protected string $printGenerator = '';
    protected string $orderType = '';
    protected string $printType = '';
    protected string $printMethod = '';
    protected string $systemType = '';

    protected array $payload = [];

    protected int $globalOrderId = 0;

    protected $orderWithItemProductPrinterCategory = [];

    protected array $additionalSettings = [
        'request_type'                          => '',
        'current_device_id'                     => 0,
        'is_print_from_device_setting_printer ' => false,
        'show_supplement_kitchen_name'          => false,
        'show_pcs_in_product_name'              => false,
        'is_discount_type_amount'               => false,
        //discount either in percentage or amount
        'is_discount_on_cart'                   => false,
        //discount either in item wise or in all cart
        //reservation settings
        'kitchen_comment' => '',
        'show_supp_kitchen_name' => false,
        'addon_print_categories' => [],
        'show_no_of_pieces' => false,
        'ayce_data' => [],

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

        //kiosk device settings
        'kioskname' => '',
        'cash_drawer_available' => 0,
        'kiosk_printer_name' => '',
        'is_print_cart_add' => 0,

        //advance data
        'thirdPartyName' => '',
        'fullreceipt' => '0',
        'kitchenreceipt' => '1',
        'categories_settings' => [],

    ];

    protected array $advanceData = [
        'tableName' => '',
        'dynamicOrderNo' => '',

        ];

    protected array $payloadRequestDetails = [];

    protected $dumpDieValue = null;
    protected array $commonRules = [];
    protected array $generatorSpecificRules = [];

    protected ?Store $store;

    protected ?array $order = [];
    protected int $orderId = 0;

    protected ?array $subOrder = [];
    protected int $subOrderId = 0;

    protected ?array $saveOrder = [];
    protected int $saveOrderId = 0;

    protected ?KioskDevice $kiosk;

    protected ?StoreReservation $reservation = null;
    protected int $reservationId = 0;

    protected ?array $orderItems;
    protected ?array $subOrderItems;
    protected ?array $saveOrderItems;
    protected ?array $reservationOrderItems;

    protected $categories = null;

    protected array $jsonItems = [];
    protected array $jsonReceipt = [];
    protected array $jsonSummary = [];
    protected int $jsonItemsIndex = 0;

    public function __construct()
    {
    }

    /**
     * @throws \Throwable
     *
     * @return array|void
     */
    public function dispatch()
    {
        try {
            $this->stage1_PrepareValidationRules();
            $this->stage2_ValidateValidations();
            $this->stage3_PrepareBasicData();
            $this->stage4_BasicDatabaseInteraction();
            $this->stage5_EnableSettings();
            $this->stage6_DatabaseInteraction();
            $this->stage7_PrepareAdvanceData();
            $this->stage8_PrepareFinalJson();

            return $this->jsonFormatFullReceipt;
        } catch (\Exception $e) {
            companionLogger('Eatcard companion Exception', $e->getMessage(), $e->getFile(), $e->getLine());
//            dd('Eatcard Exception', $e->getMessage(), $e->getFile(), $e->getLine());
            return [];
        }
    }

    /**
     * @return void
     */
    private function stage1_PrepareValidationRules()
    {
        $this->overridableCommonRule();
        $this->overridableSystemSpecificRules();
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    private function stage2_ValidateValidations()
    {
        //Note : use prepared rules and throw errors
        $this->validateCommonRules();
        $this->validateGeneratorSpecificRules();
        $this->validateExtraRules();
    }

    /**
     * @return void
     */
    private function stage3_PrepareBasicData()
    {
        $this->prepareRefOrderId();
        $this->prepareOrderData();
        $this->prepareReservationData();
        $this->prepareSubOrderData();
        $this->prepareSaveOrderData();
        $this->prepareDeviceId();
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    private function stage4_BasicDatabaseInteraction()
    {
        $this->setSubOrderData();
        $this->setOrderData();
        $this->setReservationData();
        $this->setSaveOrderData();
        $this->setStoreData();
        $this->setDeviceData();
    }

    private function stage5_EnableSettings()
    {
        $this->enableStoreSettings();
        $this->enableStoreTakeawaySetting();
        $this->enableDeviceSettings();
        $this->enableStoreReservationSettings();
    }

    private function stage6_DatabaseInteraction()
    {
        $this->setCategoryData();
    }

    private function stage7_PrepareAdvanceData()
    {
        $this->prepareTableName();
        $this->prepareDynamicOrderNo();
        $this->processOrderData();
        $this->setThirdPartyName();
        $this->prepareFullReceiptFlag();
        $this->prepareAYCEItems();
        $this->preparePaidOrderItems();
        $this->sortItems();
        $this->preparePaymentReceipt();
        $this->prepareSummary();
    }

    /**
     * @throws \Exception
     */
    private function stage8_PrepareFinalJson()
    {
        $this->setMainPrinter();
        $this->setKitchenRelatedFields();
        $this->setOpenCashDrawer();
        $this->setPrintTitles();
        $this->setDeliveryAddress();
        $this->setStoreAddress();
        $this->setLogo();
        $this->setOtherDetails();
        $this->setItems();
        $this->setReceipt();
        $this->setSummary();
    }
}
