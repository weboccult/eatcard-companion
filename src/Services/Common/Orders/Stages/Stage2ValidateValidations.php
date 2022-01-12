<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Throwable;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;

/**
 * @description Stag 2
 *
 * @author Darshit Hedpara
 */
trait Stage2ValidateValidations
{
    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateCommonRules()
    {
        try {
            foreach ($this->getCommonRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (StoreEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store not found']);
        }
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateSystemSpecificRules()
    {
        try {
            foreach ($this->getSystemSpecificRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (KioskDeviceEmptyException $e) {
            $this->setDumpDieValue(['error' => 'device not found']);
        } catch (StoreReservationEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store reservation not found']);
        }
    }

    protected function validateExtraRules()
    {
    }
}
