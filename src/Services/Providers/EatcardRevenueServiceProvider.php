<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardRevenue;

class EatcardRevenueServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('eatcard-revenue', function () {
            return new EatcardRevenue();
        });
    }
}
