<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

class PosProcessor extends BaseProcessor
{
    protected string $createdFrom = 'pos';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return bool[]
     */
    public function preparePayload(): array
    {
        return [
            'child' => true,
        ];
    }
}
