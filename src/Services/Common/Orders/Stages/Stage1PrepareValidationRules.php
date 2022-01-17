<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayDateNotAvailableException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeNotValidException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\TakeawaySettingNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\WorldLineSecretsNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @description Stag 1
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        // Add condition here... If you want to exclude store_id validation
        $this->addRuleToCommonRules(StoreEmptyException::class, (! isset($this->payload['store_id']) || empty($this->payload['store_id']) || empty($this->store)));

        if (! empty($this->device) && $this->device->payment_type == 'worldline') {
            $this->addRuleToCommonRules(WorldLineSecretsNotFoundException::class, ! file_exists(public_path('worldline/eatcard.nl.pem')));
        }
    }

    protected function overridableSystemSpecificRules()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSK])) {
            $this->addRuleToSystemSpecificRules(KioskDeviceEmptyException::class, empty($this->device));
        }
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']) && empty($this->storeReservation)) {
                $this->addRuleToSystemSpecificRules(StoreReservationEmptyException::class, empty($this->device));
            }
        }
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->addRuleToSystemSpecificRules(TakeawayOrderTypeEmptyException::class, (! isset($this->payload['order_type']) || empty($this->payload['order_type'])));
            $this->addRuleToSystemSpecificRules(TakeawayOrderTypeMisMatchedException::class, (! empty($this->payload['order_type']) && ! in_array($this->payload['order_type'], ['pickup', 'delivery'])));
            $this->addRuleToSystemSpecificRules(TakeawayPaymentMethodMisMatchedException::class, (! empty($this->payload['type']) && ! in_array($this->payload['type'], ['mollie', 'multisafepay'])));
            $this->addRuleToSystemSpecificRules(TakeawaySettingNotFoundException::class, empty($this->takeawaySetting));
            $this->addRuleToSystemSpecificRules(TakeawayPaymentMethodNotFoundException::class, (! isset($this->payload['method']) || empty($this->payload['method'])));
            if ((! isset($this->payload['order_date']) || empty($this->payload['order_date'])) || ! isset($this->payload['order_time']) || empty($this->payload['order_time'])) {
                $this->addRuleToSystemSpecificRules(TakeawayOrderDateTimeEmptyException::class, true);
                $order_date_time = $this->payload['order_date'].' '.$this->payload['order_time'].':00';
                $this->addRuleToSystemSpecificRules(TakeawayOrderDateTimeNotValidException::class, strtotime($order_date_time) < strtotime('-1 mins'));
            }

            $somePreOrderItemExist = collect($this->cart)->some('is_pre_order', '1');
            if ($this->takeawaySetting->is_single_day == 1 && ! $somePreOrderItemExist && $this->payload['order_date'] > Carbon::now()->format('Y-m-d')) {
                $this->addRuleToSystemSpecificRules(TakeawayDateNotAvailableException::class, true);
            }
        }
    }

    protected function setDeliveryZipCodeValidation()
    {
    }

    protected function setDeliveryRadiusValidation()
    {
    }
}
