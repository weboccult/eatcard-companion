<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardOrder;

/**
 *
 */
class EatcardOrderServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('eatcard-order', function () {
            return new EatcardOrder();
        });
    }
}
