<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\MultiSafe;

/**
 * @author Darshit Hedpara
 */
class MultiSafeServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('multi-safe', function () {
            return new MultiSafe();
        });
    }
}
