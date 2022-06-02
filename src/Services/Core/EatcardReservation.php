<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;

class EatcardReservation
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

    public function payload(array $payload): self
    {
        $this->processor->setPayload($payload);

        return $this;
    }

    public function simulate(): self
    {
        $this->processor->setSimulate(true);

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
