<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use Exception;

class EatcardOrder
{
    protected BaseProcessor $processor;

    /**
     * @throws Exception
     */
    public function processor(string $processor): self
    {
        if (class_exists($processor)) {
            $this->processor = new $processor();
        } else {
            throw new Exception($processor.'does not exists.!', 422);
        }

        return $this;
    }

    public function cart(array $cart): self
    {
        $this->processor->setCart($cart);

        return $this;
    }

    public function payload(array $cart): self
    {
//        $this->processor->setCart($cart);
        return $this;
    }

    /**
     * @return mixed
     */
    public function dispatch()
    {
        return $this->processor->dispatch();
    }
}
