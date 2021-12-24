<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
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
            throw new ClassNotFoundException(sprintf('Class %s not found.', $processor));
        }

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
     * @return mixed
     */
    public function dispatch()
    {
        return $this->processor->dispatch();
    }
}
