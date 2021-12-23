<?php

if (! function_exists('eatcardPrint')) {
    /**
     * Access eatcardPrint through helper.
     *
     * @return Weboccult\EatcardCompanion\Services\Core\EatcardPrint
     */
    function eatcardPrint(): Weboccult\EatcardCompanion\Services\Core\EatcardPrint
    {
        return app('eatcard-print');
    }
}

if (! function_exists('eatcardOrder')) {
    /**
     * Access eatcardOrder through helper.
     *
     * @return Weboccult\EatcardCompanion\Services\Core\EatcardOrder
     */
    function eatcardOrder(): Weboccult\EatcardCompanion\Services\Core\EatcardOrder
    {
        return app('eatcard-order');
    }
}
