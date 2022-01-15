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

    protected array $itemFormat = [];
    protected array $jsonFormatFullReceipt = [];

    protected string $printGenerator = '';
    protected string $orderType = '';
    protected string $printType = '';
    protected string $printMethod = '';
    protected string $systemType = '';

    protected array $payload = [];
    protected int $globalOrderId = 0;

    protected array $additionalSettings = [];

    protected array $advanceData = [];
    protected array $payloadRequestDetails = [];

    protected array $commonRules = [];
    protected array $generatorSpecificRules = [];

    protected ?Store $store;

    protected ?array $order = [];
    protected ?array $orderItems = [];
    protected int $orderId = 0;

    protected ?array $subOrder = [];
    protected ?array $subOrderItems = [];
    protected int $subOrderId = 0;

    protected ?array $saveOrder = [];
    protected ?array $saveOrderItems = [];
    protected int $saveOrderId = 0;

    protected ?StoreReservation $reservation = null;
    protected ?array $reservationOrderItems = [];
    protected int $reservationId = 0;

    protected ?KioskDevice $kiosk;

    protected $categories = null;

    protected int $jsonItemsIndex = 0;
    protected array $jsonItems = [];
    protected array $jsonReceipt = [];
    protected array $jsonSummary = [];

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
        $this->prepareDefaultValue();
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
        $this->enableGlobalSettings();
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
