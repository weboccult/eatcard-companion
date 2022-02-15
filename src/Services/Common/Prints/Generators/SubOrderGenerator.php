<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Generators;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;

class SubOrderGenerator extends BaseGenerator
{
    protected string $orderType = OrderTypes::SUB;

    public function __construct()
    {
        parent::__construct();
    }
}
