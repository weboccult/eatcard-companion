<?php

namespace Weboccult\EatcardCompanion;

use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Commands\EatcardCompanionPublishCommand;

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
                EatcardCompanionPublishCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/eatcard-companion'),
            ], 'eatcardcompanion-translations');
        }

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'eatcard-companion');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eatcardCompanion.php', 'eatcardCompanion');
    }
}
