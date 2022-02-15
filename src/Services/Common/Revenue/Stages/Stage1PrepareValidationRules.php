<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Exceptions\RevenueParametersEmptyException;
use Weboccult\EatcardCompanion\Exceptions\RevenuePayloadEmptyException;
use Weboccult\EatcardCompanion\Exceptions\RevenueTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;

/**
 * @description Stag 1
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        // Add condition here... If you want to exclude payload validation
        $this->addRuleToCommonRules(RevenuePayloadEmptyException::class, empty($this->payload));
        $this->addRuleToCommonRules(StoreEmptyException::class, ! isset($this->storeId));
        $this->addRuleToCommonRules(RevenueTypeEmptyException::class, empty($this->revenueType));
    }

    protected function overridableGeneratorSpecificRules()
    {
        if ($this->revenueType == RevenueTypes::DAILY) {
            $this->addRuleToGeneratorSpecificRules(RevenueParametersEmptyException::class, empty($this->date));
        }

        if ($this->revenueType == RevenueTypes::MONTHLY) {
            $this->addRuleToGeneratorSpecificRules(RevenueParametersEmptyException::class, empty($this->month));
            $this->addRuleToGeneratorSpecificRules(RevenueParametersEmptyException::class, empty($this->year));
        }
    }
}
