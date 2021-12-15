<?php

namespace Weboccult\EatcardCompanion\Services\Prints;

use Weboccult\EatcardCompanion\Services\Prints\Core\BasePrint;

/**
 *
 */
class SqsPrint extends BasePrint
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
            "123" => "asdasd"
        ];
    }
}
