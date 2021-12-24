<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

class TakeawayProcessor extends BaseProcessor
{
    protected string $createdFrom = 'takeaway';

    public function dispatch()
    {
        // TODO: Implement dispatch() method.
    }

    public function prepareValidationsRules(): array
    {
        // TODO: Implement prepareValidationsRules() method.
    }
}
