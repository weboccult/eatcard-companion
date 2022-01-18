<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\OneSignal;

/**
 * @author Darshit Hedpara
 */
class OneSignalServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('one-signal', function () {
            return new OneSignal();
        });
    }
}
