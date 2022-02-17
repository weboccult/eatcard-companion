<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardEmail;

/**
 * @author Darshit Hedpara
 */
class EatcardEmailServiceProvider extends ServiceProvider
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
        $this->app->singleton('eatcard-email', function () {
            return new EatcardEmail();
        });
    }
}
