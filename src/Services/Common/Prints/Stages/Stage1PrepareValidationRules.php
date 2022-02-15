<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\OrderTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintMethodEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintMethodNotSupportedException;
use Weboccult\EatcardCompanion\Exceptions\PrintPayloadEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\PrintTypeNotSupportedException;
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
        $this->addRuleToCommonRules(OrderIdEmptyException::class, ! isset($this->payload['order_id']));
        $this->addRuleToCommonRules(PrintMethodEmptyException::class, empty($this->printMethod));
        $this->addRuleToCommonRules(OrderTypeEmptyException::class, empty($this->orderType));
        $this->addRuleToCommonRules(PrintTypeEmptyException::class, empty($this->printType));
        $this->addRuleToCommonRules(SystemTypeEmptyException::class, empty($this->systemType));
    }

    protected function overridableGeneratorSpecificRules()
    {
        if ($this->orderType == OrderTypes::RUNNING) {
            $this->addRuleToGeneratorSpecificRules(PrintTypeNotSupportedException::class, ! in_array($this->printType, [PrintTypes::PROFORMA, PrintTypes::DEFAULT, PrintTypes::KITCHEN_LABEL, PrintTypes::KITCHEN, PrintTypes::LABEL]));
        }

        if ($this->orderType == OrderTypes::SAVE) {
            //proforma print not supported for save orders
            $this->addRuleToGeneratorSpecificRules(PrintTypeNotSupportedException::class, in_array($this->printType, [PrintTypes::PROFORMA]));
        }

        if ($this->orderType != OrderTypes::PAID && in_array($this->printMethod, [PrintMethod::PDF, PrintMethod::HTML])) {
            //PDF and HTML only supported for paid orders
            $this->addRuleToGeneratorSpecificRules(PrintMethodNotSupportedException::class, in_array($this->printType, [PrintTypes::PROFORMA]));
        }
    }
}
