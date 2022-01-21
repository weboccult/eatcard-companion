<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Exception;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Models\TakeawaySetting;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage0BasicDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage10PerformFeesCalculation;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage11CreateProcess;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage12PaymentProcess;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage13Broadcasting;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage14Notification;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage15ExtraOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage16PrepareResponse;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage4EnableSettings;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage5DatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage6preparePostValidationRulesAfterDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage7ValidatePostValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage8PrepareAdvanceData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage9PerformOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\Staggable;

/**
 * @mixin MagicAccessors
 * @mixin AttributeHelpers
 *
 * @author Darshit Hedpara
 */
abstract class BaseProcessor implements BaseProcessorContract
{
    use Staggable;
    use MagicAccessors;
    use AttributeHelpers;
    use Stage0BasicDatabaseInteraction;
    use Stage1PrepareValidationRules;
    use Stage2ValidateValidations;
    use Stage3PrepareBasicData;
    use Stage4EnableSettings;
    use Stage5DatabaseInteraction;
    use Stage6preparePostValidationRulesAfterDatabaseInteraction;
    use Stage7ValidatePostValidationRules;
    use Stage8PrepareAdvanceData;
    use Stage9PerformOperations;
    use Stage10PerformFeesCalculation;
    use Stage11CreateProcess;
    use Stage12PaymentProcess;
    use Stage13Broadcasting;
    use Stage14Notification;
    use Stage15ExtraOperations;
    use Stage16PrepareResponse;

    protected array $config;

    protected string $createdFrom = 'companion';

    protected array $payload = [];

    protected array $cart = [];

    /** @description It will be used in sub order only. */
    protected array $originalCart = [];

    /** @var Product|null|object */
    protected $productData = null;

    /** @var Supplement|null|object */
    protected $supplementData = null;

    protected string $system = 'none';

    protected bool $isSubOrder = false;

    /** @var Order|null|object */
    protected $parentOrder = null;

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var TakeawaySetting|null|object */
    protected ?TakeawaySetting $takeawaySetting;

    /** @var StoreReservation|null|object */
    protected $storeReservation = null;

    /** @var KioskDevice|null|object */
    protected $device = null;

    /** @var GiftPurchaseOrder|null|object */
    protected $coupon = null;

    protected $couponRemainingPrice = 0;

    protected array $commonRules = [];

    protected array $systemSpecificRules = [];

    /** @var array */
    protected $orderData = [];

    /** @var array<array> */
    protected $orderItemsData = [];

    protected $settings = [
        'additional_fee' => [
            'status' => false,
            'value' => null,
        ],
        'delivery_fee' => [
            'status' => false,
            'value' => null,
        ],
        'plastic_bag_fee' => [
            'status' => false,
            'value' => null,
        ],
    ];

    /** @var Order|OrderHistory|SubOrder|null|object */
    protected $createdOrder = null;

    /** @var array<array> */
    protected $createdOrderItems = [];

    /** @var null|array|object */
    protected $paymentResponse = null;

    protected $discountData = [];

    /**
     * @var array
     * @description It will check and perform after effect order creation process
     */
    protected $afterEffects = [];

    protected ?array $dumpDieValue = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (! file_exists(config_path('eatcardCompanion.php'))) {
            throw new Exception('eatcardCompanion.php not found in config folder you need publish it first.!');
        }
        $this->config = config('eatcardCompanion');
    }

    /**
     * @return array|void|null
     */
    public function dispatch()
    {
        return $this->stageIt([
            fn () => $this->stage0_BasicDatabaseInteraction(),
            fn () => $this->stage1_PrepareValidationRules(),
            fn () => $this->stage2_ValidateValidations(),
            fn () => $this->stage3_PrepareBasicData(),
            fn () => $this->stage4_EnabledSettings(),
            fn () => $this->stage5_DatabaseInteraction(),
            fn () => $this->stage6_PrepareValidationRulesAfterDatabaseInteraction(),
            fn () => $this->stage7_ValidatePostValidationRules(),
            fn () => $this->stage8_PrepareAdvanceData(),
            fn () => $this->stage9_PerformOperations(),
            fn () => $this->stage10_PerformFeesCalculation(),
            fn () => $this->stage11_CreateProcess(),
            fn () => $this->stage12_PaymentProcess(),
            fn () => $this->stage13_Broadcasting(),
            fn () => $this->stage14_Notification(),
            fn () => $this->stage15_ExtraOperations(),
            fn () => $this->stage16_Response(),
        ], true);
    }

    // Document and Developer guides
    // postFix Prepare = prepare array values into protected variable in the class
    // postFix Data = fetch data from the database and set values into protected variable in the class
    private function stage0_BasicDatabaseInteraction()
    {
        $this->stageIt([
            fn () => $this->setStoreData(),
            fn () => $this->setTakeawaySettingData(),
            fn () => $this->setParentOrderData(),
            fn () => $this->setDeviceData(),
            fn () => $this->setReservationData(),
        ]);
    }

    private function stage1_PrepareValidationRules()
    {
        $this->stageIt([
            fn () => $this->overridableCommonRule(),
            fn () => $this->overridableSystemSpecificRules(),
            fn () => $this->setTakeawayPickupValidation(),
            fn () => $this->setTakeawayDeliveryValidation(),
        ]);
    }

    private function stage2_ValidateValidations()
    {
        //Note : use prepared rules and throw errors
        $this->stageIt([
            fn () => $this->validateCommonRules(),
            fn () => $this->validateSystemSpecificRules(),
            fn () => $this->validateExtraRules(),
        ]);
    }

    private function stage3_PrepareBasicData()
    {
        $this->stageIt([
            fn () => $this->prepareUserId(),
            fn () => $this->prepareCreatedBy(),
            fn () => $this->prepareDineInType(),
            fn () => $this->prepareOrderType(),
            fn () => $this->prepareCashPaid(),
            fn () => $this->prepareCreatedFrom(),
            fn () => $this->prepareOrderStatus(),
            fn () => $this->prepareOrderBasicDetails(),
            fn () => $this->prepareSavedOrderIdData(),
            fn () => $this->prepareReservationDetails(),
        ]);
    }

    private function stage4_EnabledSettings()
    {
        $this->stageIt([
            fn () => $this->enableAdditionalFees(),
            fn () => $this->enableDeliveryFees(),
            fn () => $this->enablePlasticBagFees(),
            fn () => $this->enableStatiegeDeposite(),
            fn () => $this->enableNewLetterSubscription(),
        ]);
    }

    private function stage5_DatabaseInteraction()
    {
        $this->stageIt([
            fn () => $this->setProductData(),
            fn () => $this->setSupplementData(),
        ]);
    }

    private function stage6_PrepareValidationRulesAfterDatabaseInteraction()
    {
        $this->stageIt([
            // fn () => $this->storeExistValidation(),
            fn () => $this->reservationAlreadyCheckoutValidation(),
            fn () => $this->duplicateProductDetectedOnCart(),
        ]);
    }

    private function stage7_ValidatePostValidationRules()
    {
        $this->stageIt([
            fn () => $this->validatePostValidationRules(),
        ]);
    }

    private function stage8_PrepareAdvanceData()
    {
        $this->stageIt([
            fn () => $this->prepareOrderDiscount(),
            fn () => $this->preparePaymentMethod(),
            fn () => $this->preparePaymentDetails(),
            fn () => $this->prepareSplitPaymentDetails(),
            fn () => $this->prepareOrderId(),
            fn () => $this->prepareOrderDetails(),
            fn () => $this->prepareSupplementDetails(),
            fn () => $this->prepareOrderItemsDetails(),
            fn () => $this->prepareAllYouCanEatAmountDetails(),
            fn () => $this->calculateOrderDiscount(),
            fn () => $this->calculateFees(),
            fn () => $this->calculateStatiegeDeposite(),
            fn () => $this->calculateOriginOrderTotal(),
            fn () => $this->calculateReservationPaidAmount(),
            fn () => $this->prepareEditOrderDetails(),
        ]);
    }

    private function stage9_PerformOperations()
    {
        $this->stageIt([
            fn () => $this->editOrderOperation(),
            fn () => $this->undoOperation(),
            fn () => $this->couponOperation(),
            fn () => $this->asapOrderOperation(),
        ]);
    }

    private function stage10_PerformFeesCalculation()
    {
        // no need, already added in stage 8...
        /*$this->stageIt([
            fn () => $this->setAdditionalFees(),
            fn () => $this->setDeliveryFees(),
        ]);*/
    }

    private function stage11_CreateProcess()
    {
        $this->stageIt([
            fn () => $this->createOrder(),
            fn () => $this->createOrderItems(),
            fn () => $this->forgetSessions(),
            fn () => $this->markOtherOrderAsIgnore(),
            fn () => $this->createDeliveryIntoDatabase(),
            fn () => $this->deductCouponAmountFromPurchaseOrderOperation(),
            fn () => $this->markOrderAsFuturePrint(),
            fn () => $this->checkoutReservation(),
        ]);
    }

    private function stage12_PaymentProcess()
    {
        $this->stageIt([
            fn () => $this->ccvPayment(),
            fn () => $this->wiPayment(),
            fn () => $this->molliePayment(),
            fn () => $this->multiSafePayment(),
            fn () => $this->updateOrderReferenceIdFromPaymentGateway(),
            fn () => $this->setBypassPaymentLogicAndOverridePaymentResponse(),
        ]);
    }

    private function stage13_Broadcasting()
    {
        $this->stageIt([
            fn () => $this->sendWebNotification(),
            fn () => $this->sendAppNotification(),
            fn () => $this->socketPublish(),
            fn () => $this->newOrderSocketPublish(),
            fn () => $this->checkoutReservationSocketPublish(),
        ]);
    }

    private function stage14_Notification()
    {
        $this->stageIt([
            fn () => $this->sendEmailLogic(),
            fn () => $this->setSessionPaymentUpdate(),
            // not sure about this
        ]);
    }

    private function stage15_ExtraOperations()
    {
        $this->stageIt([
            fn () => $this->setNewLetterSubscriptionData(),
            fn () => $this->setKioskOrderAnswerChoiceLogic(),
        ]);
    }

    private function stage16_Response()
    {
        if ($this->system == SystemTypes::POS) {
            $this->posResponse();
        }

        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->takeawayResponse();
        }

        if ($this->system == SystemTypes::KIOSK) {
            $this->kioskResponse();
        }

        // anything you want
        // $this->setDumpDieValue([
        //     'order' => $this->orderData,
        //     'order_items' => $this->orderItemsData,
        // ]);
    }
}
