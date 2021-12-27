<?php

namespace Weboccult\EatcardCompanion;

use Illuminate\Support\ServiceProvider;

class EatcardCompanionServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eatcardCompanion.php', 'eatcardCompanion');
    }
}
