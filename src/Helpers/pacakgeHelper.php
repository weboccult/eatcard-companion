<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Barryvdh\DomPDF\PDF;
use Composer\InstalledVersions;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Stringable;
use Weboccult\EatcardCompanion\Enums\LoggerTypes;
use Weboccult\EatcardCompanion\Services\Core\EatcardPrint;
use Weboccult\EatcardCompanion\Services\Core\EatcardOrder;
use Weboccult\EatcardCompanion\Services\Core\EatcardSms;
use Weboccult\EatcardCompanion\Services\Core\EatcardEmail;
use Weboccult\EatcardCompanion\Services\Core\MultiSafe;
use Weboccult\EatcardCompanion\Services\Core\OneSignal;
use Weboccult\EatcardCompanion\Services\Facades\EatcardRevenue;

if (! function_exists('packageVersion')) {
    /**
     * @return string
     */
    function packageVersion(): string
    {
        try {
            return (string) InstalledVersions::getVersion('weboccult/eatcard-companion');
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
}

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
                Log::info('[ Companion package Version : '.packageVersion().'] - '.$logContent);
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
    function __companionTrans(string $path, $replace = []): string
    {
        $currentLocale = App::getLocale() ?? 'en';

        $isTranslationEnabled = config('eatcardCompanion.enable_translation');
        if (! $isTranslationEnabled) {
            // If not enabled then reset locale to EN
            App::setLocale('en');
        }

        $translatedMessage = __('eatcard-companion::'.$path, $replace);
        App::setLocale($currentLocale);

        return $translatedMessage;
    }
}

if (! function_exists('__companionPrintTrans')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function __companionPrintTrans(string $path, $replace = []): string
    {
        $currentLocale = App::getLocale() ?? 'en';

        $isPrintTranslationEnabled = config('eatcardCompanion.enable_print_translation');
        if (! $isPrintTranslationEnabled) {
            // If not enabled then reset locale to nl
            App::setLocale('en');
        }

        $translatedMessage = __('eatcard-companion::'.$path, $replace);
        App::setLocale($currentLocale);

        return $translatedMessage;
    }
}

if (! function_exists('__companionViews')) {

    /**
     * @param string $path
     * @param $data
     *
     * @return View
     */
    function __companionViews(string $path, $data = null): View
    {
        $view = view('eatcard-companion::'.$path);
        foreach ($data as $k => $v) {
            $view->with($k, $v);
        }

        return $view;
    }
}

if (! function_exists('__companionPDF')) {

    /**
     * @param string $path
     * @param $data
     *
     * @return PDF;
     */
    function __companionPDF(string $path, $data = null): PDF
    {
        $view = __companionViews($path, $data)->render();
        $pdf = app()->make('dompdf.wrapper');
        $pdf->loadHTML($view);

        return $pdf;
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
        return app(EatcardPrint::class);
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
        return app(EatcardOrder::class);
    }
}

if (! function_exists('eatcardRevenue')) {
    /**
     * Access eatcardRevenue through helper.
     *
     * @return EatcardRevenue
     */
    function eatcardRevenue(): EatcardRevenue
    {
        return app(EatcardRevenue::class);
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
        return app(MultiSafe::class);
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
        return app(OneSignal::class);
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
        return app(EatcardSms::class);
    }
}

if (! function_exists('eatcardEmail')) {
    /**
     * Access EmailManager through helper.
     *
     * @return EatcardEmail
     */
    function eatcardEmail(): EatcardEmail
    {
        return app(EatcardEmail::class);
    }
}
