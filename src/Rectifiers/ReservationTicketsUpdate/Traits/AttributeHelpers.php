<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\Traits;

/**
 * @description Manual Setters & Accessor with custom logic.
 * @mixin MagicAccessors
 */
trait AttributeHelpers
{
    /**
     * @param string $exceptionClass
     * @param bool $condition
     *
     * @return void
     */
    public function addRuleToCommonRules(string $exceptionClass, bool $condition)
    {
        $this->setCommonRules(array_merge($this->getCommonRules(), [$exceptionClass => $condition]));
    }

    /**
     * @param string $exceptionClass
     *
     * @return void
     */
    public function removeRuleFromCommonRules(string $exceptionClass)
    {
        $this->setCommonRules(collect($this->getCommonRules())
            ->reject(fn ($value, $key) => $key === $exceptionClass)
            ->toArray());
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function addMultiRuleToCommonRules(array $rules)
    {
        $this->setCommonRules(array_merge($this->getCommonRules(), $rules));
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function removeMultiRuleFromCommonRules(array $rules)
    {
        $this->setCommonRules(collect($this->getCommonRules())
            ->reject(fn ($value, $key) => in_array($key, $rules))
            ->toArray());
    }

    /**
     * @param string $exceptionClass
     * @param bool $condition
     *
     * @return void
     */
    public function addRuleToGeneratorSpecificRules(string $exceptionClass, bool $condition)
    {
        $this->setGeneratorSpecificRules(array_merge($this->getGeneratorSpecificRules(), [$exceptionClass => $condition]));
    }

    /**
     * @param string $exceptionClass
     *
     * @return void
     */
    public function removeRuleFromGeneratorSpecificRules(string $exceptionClass)
    {
        $this->setGeneratorSpecificRules(collect($this->getGeneratorSpecificRules())
            ->reject(fn ($value, $key) => $key === $exceptionClass)
            ->toArray());
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function addMultiRuleToGeneratorSpecificRules(array $rules)
    {
        $this->setGeneratorSpecificRules(array_merge($this->getGeneratorSpecificRules(), $rules));
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function removeMultiRuleFromGeneratorSpecificRules(array $rules)
    {
        $this->setGeneratorSpecificRules(collect($this->getGeneratorSpecificRules())
            ->reject(fn ($value, $key) => in_array($key, $rules))
            ->toArray());
    }
}
