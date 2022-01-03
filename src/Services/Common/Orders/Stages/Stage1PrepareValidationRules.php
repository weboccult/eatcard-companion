<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;

/**
 * @description Stag 1
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        $this->addRuleToCommonRules(StoreEmptyException::class, ! isset($this->payload['store_id']) || empty($this->payload['store_id']));
    }

    protected function overridableSystemSpecificRules()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSK])) {
            $this->addRuleToSystemSpecificRules(KioskDeviceEmptyException::class, empty($this->device));
        }
    }

    protected function setDeliveryZipCodeValidation()
    {
    }

    protected function setDeliveryRadiusValidation()
    {
    }
}
