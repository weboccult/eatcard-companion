<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 *
 */
class EatcardOrder
{
    protected BaseProcessor $processor;
    /**
     *
     */
    public function processor(string $processor): self
    {
        $this->processor = new $processor();
        return $this;
    }

    /**
     *
     */
    public function dispatch(): int
    {
        return 1;
    }
}
