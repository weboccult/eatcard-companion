<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Exception;

abstract class BaseProcessor implements BaseProcessorContract
{
    protected string $createdFrom = 'companion';

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (! $this->createdFrom == 'companion') {
            throw new Exception('You need to define value of created_from on order processor class : '.get_class($this));
        }
    }
}
