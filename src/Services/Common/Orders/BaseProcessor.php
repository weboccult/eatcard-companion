<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage10PerformFeesCalculation;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage11CreateProcess;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage12PaymentProcess;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage13Broadcasting;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage14Notification;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage15ExtraOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage4EnableSettings;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage5DatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage6preparePostValidationRulesAfterDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage7ValidatePostValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage8PrepareAdvanceData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage9PerformOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\ManualAccessors;
use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\Staggable;

/**
 * @mixin MagicAccessors
 * @mixin ManualAccessors
 *
 * @author Darshit Hedpara
 */
abstract class BaseProcessor implements BaseProcessorContract
{
    use Staggable;
    use MagicAccessors;
    use ManualAccessors;
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

    protected string $createdFrom = 'companion';

    protected array $payload = [];

    protected array $cart = [];

    protected string $system = 'none';

    protected ?Store $store;

    protected ?StoreReservation $storeReservation;

    protected ?KioskDevice $device;

    protected array $commonRules = [];

    protected array $systemSpecificRules = [];

    protected array $orderData = [];

    protected ?array $dumpDieValue = null;

    /** @var array<array> */
    protected $orderItemsData = [];

    public function __construct()
    {
    }

    /**
     * @return array|void|null
     */
    public function dispatch()
    {
        return $this->stageIt([
            fn () => $this->stag1_PrepareValidationRules(),
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
        ], true);
    }

    // Document and Developer guides
    // postFix Prepare = prepare array values into protected variable in the class
    // postFix Data = fetch data from the database and set values into protected variable in the class
    private function stag1_PrepareValidationRules()
    {
        $this->stageIt([
            fn () => $this->overridableCommonRule(),
            fn () => $this->overridableSystemSpecificRules(),
            fn () => $this->setDeliveryZipCodeValidation(),
            fn () => $this->setDeliveryRadiusValidation(),
        ]);
        dd($this);
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
            fn () => $this->enabledAdditionalFees(),
            fn () => $this->enabledDeliveryFees(),
            fn () => $this->enabledStatiegeDeposite(),
            fn () => $this->enableNewLetterSubscription(),
        ]);
    }

    private function stage5_DatabaseInteraction()
    {
        $this->stageIt([
            fn () => $this->setStoreData(),
            fn () => $this->setDeviceData(),
            fn () => $this->setProductData(),
            fn () => $this->setSupplementData(),
            fn () => $this->setReservationData(),
        ]);
    }

    private function stage6_PrepareValidationRulesAfterDatabaseInteraction()
    {
        $this->stageIt([
            fn () => $this->storeExistValidation(),
            fn () => $this->reservationAlreadyCheckoutValidation(),
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
            fn () => $this->preparePaymentDetails(),
            fn () => $this->preparePaymentMethod(),
            fn () => $this->prepareOrderId(),
            fn () => $this->prepareOrderDetails(),
            fn () => $this->prepareSupplementDetails(),
            fn () => $this->prepareOrderItemsDetails(),
            fn () => $this->prepareAyceAmountDetails(),
            fn () => $this->prepareEditOrderDetails(),
            fn () => $this->prepareUndoOrderDetails(),
            fn () => $this->prepareCouponDetails(),
        ]);
    }

    private function stage9_PerformOperations()
    {
        $this->stageIt([
            fn () => $this->editOrderOperation(),
            fn () => $this->undoOperation(),
            fn () => $this->couponOperation(),
            fn () => $this->deductCouponAmountFromPurchaseOrderOperation(),
        ]);
    }

    private function stage10_PerformFeesCalculation()
    {
        $this->stageIt([
            fn () => $this->setAdditionalFees(),
            fn () => $this->setDeliveryFees(),
        ]);
    }

    private function stage11_CreateProcess()
    {
        $this->stageIt([
            fn () => $this->createOrder(),
            fn () => $this->createOrderItems(),
            fn () => $this->markOrderAsFuturePrint(),
            fn () => $this->checkoutReservation(),
            fn () => $this->createDeliveryIntoDatabase(),
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
}
