<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage10PaymentProcess;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage11Broadcasting;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage12Notification;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage13ExtraOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage4EnableSettings;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage5DatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage6PrepareAdvanceData;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage7PerformOperations;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage8PerformFeesCalculation;
use Weboccult\EatcardCompanion\Services\Common\Orders\Stages\Stage9CreateProcess;

abstract class BaseProcessor implements BaseProcessorContract
{
    use Stage1PrepareValidationRules;
    use Stage2ValidateValidations;
    use Stage3PrepareBasicData;
    use Stage4EnableSettings;
    use Stage5DatabaseInteraction;
    use Stage6PrepareAdvanceData;
    use Stage7PerformOperations;
    use Stage8PerformFeesCalculation;
    use Stage9CreateProcess;
    use Stage10PaymentProcess;
    use Stage11Broadcasting;
    use Stage12Notification;
    use Stage13ExtraOperations;

    protected string $createdFrom = 'companion';

    protected array $payload = [];

    protected array $cart = [];

    protected string $system = 'none';

    protected ?Store $store;

    protected ?StoreReservation $storeReservation;

    protected ?KioskDevice $device;

    public function __construct()
    {
    }

    /**
     * @param string $system
     */
    public function setSystem(string $system): void
    {
        $this->system = $system;
    }

    /**
     * @param array $cart
     */
    public function setCart(array $cart): void
    {
        $this->cart = $cart;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function dispatch(): array
    {
        $this->stag1PrepareValidationRules();
        $this->stage2ValidateValidations();
        $this->stage3PrepareBasicData();
        $this->enabledSettings();
        $this->databaseInteraction();
        $this->prepareAdvanceData();
        $this->performOperations();
        $this->performFeesCalculation();
        $this->createProcess();
        $this->paymentProcess();
        $this->broadcasting();
        $this->notification();
        $this->extraOperations();

        return [
            'output' => 'anything...',
        ];
    }

    // Document and Developer guides
    // postFix Prepare = prepare array values into protected variable in the class
    // postFix Data = fetch data from the database and set values into protected variable in the class
    private function stag1PrepareValidationRules()
    {
        $this->prepareOverridableCommonRule();
        $this->overridableRulesSystemSpecific();
        $this->multiCheckoutValidation();
        $this->setDeliveryZipCodeValidation();
        $this->setDeliveryRadiusValidation();
    }

    private function stage2ValidateValidations()
    {
        //Note : use prepared rules and throw errors
        $this->validateCommonRules();
        $this->validateSystemSpecificRules();
        $this->validateExtraRules();
    }

    private function stage3PrepareBasicData()
    {
        $this->prepareUserId();
        $this->prepareCreatedFrom();
        $this->prepareOrderStatus();
        $this->prepareOrderBasicDetails();
        $this->prepareSavedOrderIdData();
        $this->prepareReservationDetails();
    }

    private function enabledSettings()
    {
        $this->enabledAdditionalFees();
        $this->enabledDeliveryFees();
        $this->enabledStatiegeDeposite();
        $this->enableNewLetterSubscription();
    }

    private function databaseInteraction()
    {
        $this->setDeviceData();
        $this->setProductData();
        $this->setSupplementData();
        $this->setReservationData();
    }

    private function prepareAdvanceData()
    {
        $this->prepareOrderDiscount();
        $this->preparePaymentDetails();
        $this->preparePaymentMethod();
        $this->prepareOrderId();
        $this->prepareOrderDetails();
        $this->prepareSupplementDetails();
        $this->prepareOrderItemsDetails();
        $this->prepareAyceAmountDetails();
        $this->prepareEditOrderDetails();
        $this->prepareUndoOrderDetails();
        $this->prepareCouponDetails();
    }

    private function performOperations()
    {
        $this->editOrderOperation();
        $this->undoOperation();
        $this->couponOperation();
        $this->deductCouponAmountFromPurchaseOrderOperation();
    }

    private function performFeesCalculation()
    {
        $this->setAdditionalFees();
        $this->setDeliveryFees();
    }

    private function createProcess()
    {
        $this->createOrder();
        $this->createOrderItems();
        $this->markOrderAsFuturePrint();
        $this->checkoutReservation();
        $this->createDeliveryIntoDatabase();
    }

    private function paymentProcess()
    {
        $this->ccvPayment();
        $this->wiPayment();
        $this->molliePayment();
        $this->multiSafePayment();
        $this->updateOrderReferenceIdFromPaymentGateway();
        $this->setBypassPaymentLogicAndOverridePaymentResponse();
    }

    private function broadcasting()
    {
        $this->sendWebNotification();
        $this->sendAppNotification();
        $this->socketPublish();
        $this->newOrderSocketPublish();
        $this->checkoutReservationSocketPublish();
    }

    private function notification()
    {
        $this->sendEmailLogic();
        $this->setSessionPaymentUpdate(); // not sure about this
    }

    private function extraOperations()
    {
        $this->setNewLetterSubscriptionData();
        $this->setKioskOrderAnswerChoiceLogic();
    }
}
