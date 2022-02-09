<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Generators;

use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;

class MonthlyRevenueGenerator extends BaseGenerator
{
    protected string $revenueType = RevenueTypes::MONTHLY;

    public function __construct()
    {
        parent::__construct();
    }
}
