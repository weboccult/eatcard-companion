<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardReservation;

class EatcardReservationServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->bind('eatcard-reservation', function () {
            return new EatcardReservation();
        });
    }
}
