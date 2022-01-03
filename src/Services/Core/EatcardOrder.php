<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

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
            throw new ClassNotFoundException($processor);
        }

        return $this;
    }

    public function system(string $system): self
    {
        $this->processor->setSystem($system);

        return $this;
    }

    public function cart(array $cart): self
    {
        $this->processor->setCart($cart);

        return $this;
    }

    public function payload(array $payload): self
    {
        $this->processor->setPayload($payload);

        return $this;
    }

    /**
     * @return array|void|null
     */
    public function dispatch()
    {
        return $this->processor->dispatch();
    }
}
