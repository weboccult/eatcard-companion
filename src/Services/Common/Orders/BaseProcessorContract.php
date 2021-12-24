<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

interface BaseProcessorContract
{
    public function dispatch();

    public function validate();

    public function prepareValidationsRules();
}
