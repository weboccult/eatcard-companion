<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Throwable;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 2
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
        companionLogger('----Companion Print : ', 'common rules', $this->getCommonRules());
        foreach ($this->getCommonRules() as $ex => $condition) {
            throw_if($condition, new $ex());
        }
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateGeneratorSpecificRules()
    {
        companionLogger('----Companion Print : ', 'generator rules', $this->getGeneratorSpecificRules());
        foreach ($this->getGeneratorSpecificRules() as $ex => $condition) {
            throw_if($condition, new $ex());
        }
    }

    protected function validateExtraRules()
    {
    }
}
