<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Weboccult\EatcardCompanion\Enums\LoggerTypes;
use Weboccult\EatcardCompanion\Services\Core\EatcardPrint;
use Weboccult\EatcardCompanion\Services\Core\EatcardOrder;
use Weboccult\EatcardCompanion\Services\Core\EatcardSms;
use Weboccult\EatcardCompanion\Services\Core\MultiSafe;
use Weboccult\EatcardCompanion\Services\Core\OneSignal;

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

if (! function_exists('__companionTrans')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function __companionTrans(string $path): string
    {
        $isTranslationEnabled = config('eatcardCompanion.enable_translation');
        if (! $isTranslationEnabled) {
            // If not enabled then reset locale to EN
            App::setLocale('en');
        }

        return __('eatcard-companion::'.$path);
    }
}

if (! function_exists('__companionPrintTrans')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function __companionPrintTrans(string $path): string
    {
        $isPrintTranslationEnabled = config('eatcardCompanion.enable_print_translation');
        if (! $isPrintTranslationEnabled) {
            // If not enabled then reset locale to EN
            App::setLocale('en');
        }

        return __('eatcard-companion::'.$path);
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

if (! function_exists('multiSafe')) {
    /**
     * Access MultiSafe through helper.
     *
     * @return MultiSafe
     */
    function multiSafe(): MultiSafe
    {
        return app('multi-safe');
    }
}

if (! function_exists('oneSignal')) {
    /**
     * Access SmsManager through helper.
     *
     * @return OneSignal
     */
    function oneSignal(): OneSignal
    {
        return app('sms');
    }
}

if (! function_exists('eatcardSms')) {
    /**
     * Access SmsManager through helper.
     *
     * @return EatcardSms
     */
    function eatcardSms(): EatcardSms
    {
        return app('eatcard-sms');
    }
}
