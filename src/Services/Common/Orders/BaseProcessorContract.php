<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

interface BaseProcessorContract
{
//    public function validate(array $rules);
//
//    public function prepareValidationsRules();

    public function dispatch();
}
