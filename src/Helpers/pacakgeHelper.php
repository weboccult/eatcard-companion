<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Weboccult\EatcardCompanion\Enums\LoggerTypes;

if (! function_exists('companionLogger')) {
    /**
     * Access companionLogger through helper.
     *
     * @param mixed ...$values
     *
     * @return void
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
