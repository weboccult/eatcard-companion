<?php

namespace Weboccult\EatcardCompanion;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Commands\EatcardCompanionConfigPublishCommand;

/**
 * @author Darshit Hedpara
 */
class EatcardCompanionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/eatcardCompanion.php' => config_path('eatcardCompanion.php'),
            ], 'eatcardcompanion-config');
            // Registering package commands.
            $this->commands([
                EatcardCompanionConfigPublishCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eatcardCompanion.php', 'eatcardCompanion');
    }
}
