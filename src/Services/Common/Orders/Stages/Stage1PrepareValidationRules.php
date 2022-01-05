<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;

/**
 * @description Stag 1
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        // Add condition here... If you want to exclude store_id validation
        $this->addRuleToCommonRules(StoreEmptyException::class, (! isset($this->payload['store_id']) || empty($this->payload['store_id']) || empty($this->store)));
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
    }

    protected function setDeliveryZipCodeValidation()
    {
    }

    protected function setDeliveryRadiusValidation()
    {
    }
}
