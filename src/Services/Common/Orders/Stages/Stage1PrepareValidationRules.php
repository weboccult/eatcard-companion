<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
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
    }

    protected function setDeliveryZipCodeValidation()
    {
    }

    protected function setDeliveryRadiusValidation()
    {
    }
}
