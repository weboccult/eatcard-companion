<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Weboccult\EatcardCompanion\Enums\LoggerTypes;
use Weboccult\EatcardCompanion\Services\Core\EatcardPrint;
use Weboccult\EatcardCompanion\Services\Core\EatcardOrder;

if (! function_exists('companionLogger')) {
    /**
     * Access companionLogger through helper.
     *
     * @param mixed ...$values
     *
     * @return void
     *
     * @author Darshit Hedpara
     */
    function companionLogger(...$values): void
    {
        $driver = 'FILE';
        $settings = config('eatcardCompanion');
        if (! empty($settings) && ! empty($settings['logger'] && $settings['logger']['enabled'] == true)) {
            try {
                if (LoggerTypes::isValidName($settings['logger']['driver'])) {
                    $driver = $settings['logger']['driver'];
                }
            } catch (Exception $e) {
            }
        }
        $logContent = collect($values)->pipeInto(Stringable::class)->jsonSerialize();
        switch ($driver) {
            case LoggerTypes::FILE:
                Log::info($logContent);
                break;
            case LoggerTypes::CLOUDWATCH:
                break;
        }
    }
}

if (! function_exists('eatcardPrint')) {
    /**
     * Access eatcardPrint through helper.
     *
     * @return EatcardPrint
     */
    function eatcardPrint(): EatcardPrint
    {
        return app('eatcard-print');
    }
}

if (! function_exists('eatcardOrder')) {
    /**
     * Access eatcardOrder through helper.
     *
     * @return EatcardOrder
     */
    function eatcardOrder(): EatcardOrder
    {
        return app('eatcard-order');
    }
}
