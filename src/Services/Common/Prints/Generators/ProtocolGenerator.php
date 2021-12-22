<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Generators;

use Weboccult\EatcardCompanion\Services\Common\Prints\BasePrint;

/**
 *
 */
class ProtocolGenerator extends BasePrint
{
    public function validate()
    {
    }

    /**
     * @return string[]
     */
    public function dispatch(): array
    {
        return [
            '123' => 'asdasd',
        ];
    }
}
