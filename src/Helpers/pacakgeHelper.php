<?php

if (! function_exists('eatcardPrint')) {
    /**
     * Access eatcardPrint through helper.
     *
     * @return Weboccult\EatcardCompanion\Services\Prints\Core\EatcardPrint
     */
    function eatcardPrint(): Weboccult\EatcardCompanion\Services\Prints\Core\EatcardPrint
    {
        return app('eatcard-print');
    }
}
