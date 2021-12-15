<?php

namespace Weboccult\EatcardCompanion\Services\Prints\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Prints\Core\EatcardPrint;
use Weboccult\LaravelAwsCloudWatchLogger\LaravelAwsCloudWatchLogger;

class EatcardPrintServiceProvider extends ServiceProvider
{

    public function boot() {}

    public function register() {
        $this->app->singleton('eatcard-print', function () {
            return new EatcardPrint;
        });
    }
}
