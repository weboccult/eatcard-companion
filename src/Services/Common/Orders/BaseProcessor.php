<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;

abstract class BaseProcessor implements BaseProcessorContract
{
    protected string $createdFrom = 'companion';

    protected string $orderStatus = 'received';

    protected string $createdBy = '';

    protected string $savedOrderId = '';

    protected array $payload = [];

    protected array $cart = [];

    protected ?Store $store;

    protected ?KioskDevice $device;

    public function __construct()
    {
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
     * @return bool[]
     */
    protected function preparePayload(): array
    {
        return [
            'parent' => true,
        ];
    }

    public function dispatch()
    {
        // $this->prepareValidationRules();
        // $this->validateValidations();
        // $this->prepareBasicData();
        // $this->enabledSettings();
        // $this->databaseInteraction();
        // $this->prepareAdvanceData();
        // $this->performOperations();
        // $this->performFeesCalculation();
        // $this->createProcess();
        // $this->payment();
        // $this->broadcasting();
        // $this->notification();
        // $this->extraOperations();
    }

    // Document and Developer guides

    // postFix Prepare = prepare array values into protected variable in the class
    // postFix Data = fetch data from the database and set values into protected variable in the class

    // prepareValidationRules()
    //        protected function prepareOverridableCommonRule() {}
    //        protected function overridableRulesSystemSpecific() {}
    //        protected function multiCheckoutValidation() {}
    //        protected function setDeliveryZipCodeValidation() {}
    //        protected function setDeliveryRadiusValidation() {}
    //        protected function setDeliveryRadiusValidation() {}

    // validateValidations()
    //Note : use prepared rules and throw errors
    //        protected function validateCommonRules() {}
    //        protected function validateSystemSpecificRules() {}
    //        protected function validateExtraRules() {}

    // prepareBasicData()
    //        protected function prepareUserId() {}
    //        protected function prepareCreatedFrom() {}
    //        protected function prepareOrderStatus() {}
    //        protected function prepareOrderBasicDetails() {}
    //        protected function prepareSavedOrderIdData() {}
    //        protected function prepareReservationDetails() {}

    // enabledSettings()
    //        protected function enabledAdditionalFees() {}
    //        protected function enabledDeliveryFees() {}
    //        protected function enabledStatiegeDeposite() {}
    //        protected function enableNewLetterSubscription() {}

    // databaseInteraction()
    //        protected function setDeviceData() {}
    //        protected function setProductData() {}
    //        protected function setSupplementData() {}
    //        protected function setReservationData() {}

    // prepareAdvanceData()
    //        protected function prepareOrderDiscount() {}
    //        protected function preparePaymentDetails() {}
    //        protected function preparePaymentMethod() {}
    //        protected function prepareOrderId() {}
    //        protected function prepareOrderDetails() {}
    //        protected function prepareSupplementDetails() {}
    //        protected function prepareOrderItemsDetails() {}
    //        protected function prepareAyceAmountDetails() {}
    //        protected function prepareEditOrderDetails() {}
    //        protected function prepareUndoOrderDetails() {}
    //        protected function prepareCouponDetails() {}

    // performOperations()
    //        protected function editOrderOperation() {}
    //        protected function undoOperation() {}
    //        protected function couponOperation() {}
    //        protected function deductCouponAmountFromPurchaseOrderOperation() {}

    // performFeesCalculation()
    //        protected function setAdditionalFees() {}
    //        protected function setDeliveryFees() {}

    // createProcess()
    //        protected function createOrder() {}
    //        protected function createOrderItems() {}
    //        protected function markOrderAsFuturePrint() {}
    //        protected function checkoutReservation() {}
    //        protected function createDeliveryIntoDatabase() {}

    // payment()
    //        protected function ccvPayment() {}
    //        protected function wiPayment() {}
    //        protected function molliePayment() {}
    //        protected function multiSafePayment() {}
    //        protected function updateOrderReferenceIdFromPaymentGateway() {}
    //        protected function setBypassPaymentLogicAndOverridePaymentResponse() {}

    // broadcasting()
    //        protected function sendWebNotification() {}
    //        protected function sendAppNotification() {}
    //        protected function socketPublish() {}
    //        protected function newOrderSocketPublish() {}
    //        protected function checkoutReservationSocketPublish() {}

    // notification()
    //        protected function sendEmailLogic() {}
    //        protected function setSessionPaymentUpdate() {} // not sure about this

    // extraOperations()
    //        protected function setNewLetterSubscriptionData() {}
    //        protected function setKioskOrderAnswerChoiceLogic() {}
}
