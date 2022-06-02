<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Traits;

/**
 * @description Manual Getter Setters & Accessor with custom logic.
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
    public function addRuleToSystemSpecificRules(string $exceptionClass, bool $condition)
    {
        $this->setSystemSpecificRules(array_merge($this->getSystemSpecificRules(), [$exceptionClass => $condition]));
    }

    /**
     * @param string $exceptionClass
     *
     * @return void
     */
    public function removeRuleFromSystemSpecificRules(string $exceptionClass)
    {
        $this->setSystemSpecificRules(collect($this->getSystemSpecificRules())
            ->reject(fn ($value, $key) => $key === $exceptionClass)
            ->toArray());
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function addMultiRuleToSystemSpecificRules(array $rules)
    {
        $this->setSystemSpecificRules(array_merge($this->getSystemSpecificRules(), $rules));
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function removeMultiRuleFromSystemSpecificRules(array $rules)
    {
        $this->setSystemSpecificRules(collect($this->getSystemSpecificRules())
            ->reject(fn ($value, $key) => in_array($key, $rules))
            ->toArray());
    }
}
