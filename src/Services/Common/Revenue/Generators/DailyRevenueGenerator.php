<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Generators;

use Weboccult\EatcardCompanion\Enums\RevenueTypes;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;

class DailyRevenueGenerator extends BaseGenerator
{
    protected string $revenueType = RevenueTypes::DAILY;

    public function __construct()
    {
        parent::__construct();
    }
}
