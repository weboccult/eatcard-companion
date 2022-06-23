<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\Traits;

/**
 * @description Multi Stage manager to dump and die at any point...
 */
trait Staggable
{
    /**
     * @param array $callables
     * @param bool $higherStage
     *
     * @return array|void|null
     */
    private function stageIt(array $callables, bool $higherStage = false)
    {
        foreach ($callables as $callable) {
            if (is_callable($callable)) {
                $callable();
                if (! empty($this->dumpDieValue)) {
                    if ($higherStage) {
                        return $this->dumpDieValue;
                    } else {
                        return;
                    }
                }
            }
        }
    }
}
