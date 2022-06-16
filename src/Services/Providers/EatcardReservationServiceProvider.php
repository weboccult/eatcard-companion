<?php

namespace Weboccult\EatcardCompanion\Services\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Services\Core\EatcardReservation;

class EatcardReservationServiceProvider extends ServiceProvider
{
    public function boot()
    {
//        Relation::morphMap([
//            'StoreReservation' => 'Weboccult\EatcardCompanion\Models\StoreReservation',
//        ]);
    }

    public function register()
    {
        $this->app->bind('eatcard-reservation', function () {
            return new EatcardReservation();
        });
    }
}
