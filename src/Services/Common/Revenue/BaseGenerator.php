<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue;

use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage4BasicDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage5EnableSettings;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage6PrepareAdvanceData;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Stages\Stage7PrepareFinalData;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Traits\MagicAccessors;
use function Weboccult\EatcardCompanion\Helpers\__companionPDF;
use function Weboccult\EatcardCompanion\Helpers\__companionViews;
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
    use Stage6PrepareAdvanceData;
    use Stage7PrepareFinalData;

    protected array $payload = [];
    protected int $storeId = 0;
    protected string $revenueType = '';
    protected string $revenueMethod = '';
    protected string $date = '';
    protected int $month = 0;
    protected int $year = 0;

    protected array $commonRules = [];
    protected array $generatorSpecificRules = [];

    protected array $finalJson = [];
    protected array $detailJson = [];
    protected array $totalJson = [];
    protected array $summaryJson = [];
    protected array $matrixJson = [];

    protected ?Store $store;

    protected $startDate = null;
    protected $endDate = null;
    protected array $additionalSettings = [];
    protected array $calcData = [];

    protected array $finalData = [];
    protected array $finalOrderDetail = [];

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
            $this->stage6_PrepareAdvanceData();
            $this->stage7_PrepareFinalData();

            if ($this->revenueType == RevenueTypes::MONTHLY) {
                $order_detail = $this->finalOrderDetail;
                $store = $this->store;
                $data = $this->finalData;
//               dd($order_detail,$store,$data);
                if ($this->revenueMethod == PrintMethod::PDF) {
                    return __companionPDF('revenue.takeaway-monthly-new-revenue', ['order_detail'=>$order_detail, 'store'=> $store, 'data'=>$data])
                       ->download($this->month.'-'.$this->year.'-month_turnover'.'.pdf');
                } else {
                    return __companionViews('revenue.takeaway-monthly-new-revenue', compact('order_detail', 'store', 'data'))->render();
                }
            }

            if ($this->revenueType == RevenueTypes::DAILY) {
                $store = $this->store;
                $data = $this->finalData;
//               dd($store,$data);
                if ($this->revenueMethod == PrintMethod::PDF) {
                    return __companionPDF('revenue.takeaway-order-new-revenue', ['store'=> $store, 'data'=>$data])
                       ->download($this->month.'-'.$this->year.'-month_turnover'.'.pdf');
                } elseif ($this->additionalSettings['is_Print']) {
                    return $this->finalJson;
                } else {
                    return __companionViews('revenue.takeaway-order-new-revenue', compact('store', 'data'))->render();
                }
            }

//            return [
//                'payload' => $this->payload,
//                'storeId' => $this->storeId,
//                'revenueType' => $this->revenueType,
//                'date' => $this->date,
//                'month' => $this->month,
//                'year' => $this->year,
//                'startDate' => $this->startDate,
//                'endDate' => $this->endDate,
//                'store' => $this->store,
//                'additionalSettings' => $this->additionalSettings,
//                'calcData' => $this->calcData,
//                'finalJson' => $this->finalJson,
//                //               '' => $this->,
//            ];
        } catch (\Exception $e) {
            dd($e);
            dd($e->getMessage(), $e->getFile(), $e->getLine());
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
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    private function stage4_BasicDatabaseInteraction()
    {
        $this->setStoreData();
    }

    private function stage5_EnableSettings()
    {
        $this->enableStoreSettings();
        $this->enableDeviceSettings();
        $this->enableStorePosSettings();
        $this->enableSearchDates();
        $this->enableGlobalSettings();
    }

    private function stage6_PrepareAdvanceData()
    {
        $this->prepareOrderDateRelatedDetails();
        $this->prepareOrderCreateDateRelatedDetails();
        $this->prepareStoreReservationDetails();
        $this->prepareGiftPurchaseOrderDetails();
        $this->prepareDrawerCountDetails();
        $this->prepareSummary();
    }

    private function stage7_PrepareFinalData()
    {
        $this->setCommonData();
        $this->setGeneratorSpecificData();

        if ($this->additionalSettings['is_Print']) {
            $this->setMainPrinter();
            $this->setPrintTitles();
            $this->setSummaryTop();
            $this->setMatrixDetails();
            $this->setSummaryBottom();
            $this->setFooter();
        }
    }
}
