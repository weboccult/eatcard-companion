<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardSms;

/**
 * @author Darshit Hedpara
 */
class EatcardSmsServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('eatcard-sms', function () {
            return new EatcardSms(config('eatcardSms'));
        });
    }
}
