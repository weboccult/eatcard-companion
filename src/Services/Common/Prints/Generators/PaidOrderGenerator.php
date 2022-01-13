<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Generators;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;

class PaidOrderGenerator extends BaseGenerator
{
    public function __construct()
    {
        parent::__construct();

        $this->setOrderType(OrderTypes::PAID);
    }
}
