<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\AYCEDataEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DineInPriceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\FromTimeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\MealEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationDateEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationTypeInvalidException;
use Weboccult\EatcardCompanion\Exceptions\SlotEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TableEmptyException;
use Weboccult\EatcardCompanion\Exceptions\UserEmptyException;
use Weboccult\EatcardCompanion\Exceptions\WorldLineSecretsNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;

/**
 * @description Stag 1
 * @mixin BaseProcessor
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        $this->reservationDate = $this->payload['res_date'] ?? '';

        // Add condition here... If you want to exclude store_id validation
        $this->addRuleToCommonRules(StoreEmptyException::class, (empty($this->store)));
        $this->addRuleToCommonRules(MealEmptyException::class, (empty($this->meal)));
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            $this->addRuleToCommonRules(SlotEmptyException::class, (empty($this->slot)));
        }

        if ($this->system == SystemTypes::POS) {
            $this->addRuleToCommonRules(TableEmptyException::class, (empty($this->table)));
            $this->addRuleToCommonRules(UserEmptyException::class, (empty($this->payload['user_id'] ?? 0)));
            $this->addRuleToCommonRules(FromTimeEmptyException::class, (empty($this->payload['from_time'] ?? 0)));
        }

        $this->addRuleToCommonRules(ReservationDateEmptyException::class, (empty($this->reservationDate)));

        if (! empty($this->device) && $this->device->payment_type == 'worldline') {
            $this->addRuleToCommonRules(WorldLineSecretsNotFoundException::class, ! file_exists(public_path('worldline/eatcard.nl.pem')));
        }
    }

    protected function overridableSystemSpecificRules()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS])) {
            $this->addRuleToSystemSpecificRules(DeviceEmptyException::class, empty($this->device));

            if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']) && empty($this->storeReservation)) {
                $this->addRuleToSystemSpecificRules(StoreReservationEmptyException::class, empty($this->device));
            }

            if (isset($this->payload['dinein_price_id']) && ! empty($this->payload['dinein_price_id'])) {
                $this->addRuleToSystemSpecificRules(DineInPriceEmptyException::class, empty($this->device));
            }

            if (isset($this->payload['ayceData']) && ! empty($this->payload['ayceData'])) {
                $this->addRuleToSystemSpecificRules(AYCEDataEmptyException::class, empty($this->device));
            }

            $reservationType = $this->payload['reservation_type'] ?? '';
            if (empty($reservationType) || $reservationType != 'all_you_eat') {
                $this->addRuleToSystemSpecificRules(ReservationTypeInvalidException::class, empty($this->device));
            }
        }
    }
}
