<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\OrderTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintMethodEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintPayloadEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\SystemTypeEmptyException;

/**
 * @description Stag 1
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        // Add condition here... If you want to exclude payload validation
        $this->addRuleToCommonRules(PrintPayloadEmptyException::class, empty($this->payload));
        $this->addRuleToGeneratorSpecificRules(OrderIdEmptyException::class, ! isset($this->payload['order_id']));
        $this->addRuleToGeneratorSpecificRules(PrintMethodEmptyException::class, empty($this->printMethod));
        $this->addRuleToGeneratorSpecificRules(OrderTypeEmptyException::class, empty($this->orderType));
        $this->addRuleToGeneratorSpecificRules(PrintTypeEmptyException::class, empty($this->printType));
        $this->addRuleToGeneratorSpecificRules(SystemTypeEmptyException::class, empty($this->systemType));
    }

    protected function overridableSystemSpecificRules()
    {
    }
}
