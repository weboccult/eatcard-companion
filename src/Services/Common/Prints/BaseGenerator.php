<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints;

use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Models\KdsUser;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\OrderReceipt;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
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
use function Weboccult\EatcardCompanion\Helpers\__companionPDF;
use function Weboccult\EatcardCompanion\Helpers\__companionViews;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\__companionPrintTrans;

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

    protected array $itemFormat = [];
    protected array $jsonFormatFullReceipt = [];

    protected string $printGenerator = '';
    protected string $orderType = '';
    protected string $printType = '';
    protected string $printMethod = '';
    protected string $systemType = '';

    protected array $payload = [];
    protected int $globalOrderId = 0;
    protected int $globalItemId = 0;

    protected ?KdsUser $kdsUser = null;
    protected int $kdsUserId = 0;

    protected array $additionalSettings = [];

    protected array $advanceData = [];
    protected array $payloadRequestDetails = [];

    protected array $commonRules = [];
    protected array $generatorSpecificRules = [];

    protected ?Store $store;

    protected ?array $order = [];
    protected ?array $orderItems = [];
    protected int $orderId = 0;
    protected int $orderItemId = 0;

    protected ?array $subOrder = [];
    protected ?array $subOrderItems = [];
    protected int $subOrderId = 0;

    protected ?OrderReceipt $saveOrder = null;
    protected ?array $saveOrderItems = [];
    protected int $saveOrderId = 0;
    protected int $saveOrderItemCartId = 0;

    protected ?StoreReservation $reservation = null;
    protected ?ReservationOrderItem $reservationOrderItems = null;
    protected int $reservationId = 0;
    protected int $reservationOrderItemId = 0;
    protected string $reservationOrderItemCartId = '';

    protected ?KioskDevice $kiosk;

    protected $categories = null;

    protected float $total_0_tax_amount = 0;
    protected float $total_9_tax_amount = 0;
    protected float $total_21_tax_amount = 0;

    protected int $jsonItemsIndex = 0;
    protected array $jsonItems = [];
    protected array $jsonReceipt = [];
    protected array $jsonPreSummary = [];
    protected array $jsonSubTotal = [];
    protected array $jsonTaxDetail = [];
    protected array $jsonSummary = [];
    protected array $jsonPaymentSummary = [];
    protected array $jsonGeneralComments = [];

    protected bool $skipMainPrint = false;
    protected bool $skipKitchenLabelPrint = false;

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

            $data = $this->jsonFormatFullReceipt;
            $store = $this->store;
            $kiosk = $this->kiosk;
            $order = $this->order;
            $test = __companionPrintTrans('general.online_payment');

            if ($this->printMethod == PrintMethod::PROTOCOL || $this->printType == PrintMethod::SQS) {
                return $this->jsonFormatFullReceipt;
            } elseif ($this->printMethod == PrintMethod::HTML && ! empty($data)) {
                return __companionViews('order.order-details', ['data'=>$data, 'order' => $order, 'store'=> $store, 'kiosk'=>$kiosk, 'test' => $test])
                                       ->render();
            } elseif ($this->printMethod == PrintMethod::PDF && ! empty($data)) {
                return __companionPDF('order.order-details', ['data'=>$data, 'order' => $order, 'store'=> $store, 'kiosk'=>$kiosk, 'test' => $test])
                       ->download('orderno-'.$this->globalOrderId.'.pdf');
            }
        } catch (\Exception $e) {
            companionLogger('Eatcard companion Exception', $e->getMessage(), $e->getFile(), $e->getLine());

            return [];
        }
    }

    /**
     * @return void
     */
    private function stage1_PrepareValidationRules()
    {
        $this->overridableCommonRule();
        $this->overridableGeneratorSpecificRules();
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
        $this->prepareDefaultValue();
        $this->preparePayloadData();
        $this->prepareOrderData();
        $this->prepareReservationData();
        $this->prepareReservationOrderItemData();
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
        $this->setReservationOrderItemData();
        $this->setOrderData();
        $this->setReservationData();
        $this->setSaveOrderData();
        $this->setStoreData();
        $this->setDeviceData();
        $this->setKDSUserData();
    }

    private function stage5_EnableSettings()
    {
        $this->enableStoreSettings();
        $this->enableStoreTakeawaySetting();
        $this->enableDeviceSettings();
        $this->enableStoreReservationSettings();
        $this->enableGlobalSettings();
    }

    private function stage6_DatabaseInteraction()
    {
        $this->setCategoryData();
    }

    private function stage7_PrepareAdvanceData()
    {
        $this->prepareDynamicOrderNo();
        $this->prepareTableName();
        $this->processOrderData();
        $this->setThirdPartyName();
        $this->prepareFullReceiptFlag();
        $this->prepareAYCEItems();
        $this->preparePaidOrderItems();
        $this->prepareRunningOrderItems();
        $this->prepareSaveOrderItems();
        $this->sortItems();
        $this->preparePaymentReceipt();
        $this->preparePreSummary();
        $this->prepareSubTotal();
        $this->prepareTaxDetail();
        $this->prepareGeneralComments();
        $this->prepareSummary();
        $this->preparePaymentSummary();
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
        $this->setPreSummary();
        $this->setSubTotal();
        $this->setTaxDetail();
        $this->setGeneralComments();
        $this->setSummary();
        $this->setPaymentSummary();
    }

    //global functions

    /**
     * @param $item
     *
     * @return void
     */
    protected function itemPricesCalculate($item, $isAYCEProduct = false)
    {
    }
}
