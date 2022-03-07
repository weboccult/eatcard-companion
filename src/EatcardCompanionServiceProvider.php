<?php

namespace Weboccult\EatcardCompanion;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Weboccult\EatcardCompanion\Commands\EatcardCompanionPublishCommand;
use Weboccult\EatcardCompanion\Commands\EatcardSmsPublishCommand;

/**
 * @author Darshit Hedpara
 */
class EatcardCompanionServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/eatcardCompanion.php' => config_path('eatcardCompanion.php'),
            ], 'eatcardcompanion-config');

            // Registering package commands.
            $this->commands([
                EatcardCompanionPublishCommand::class,
                EatcardSmsPublishCommand::class,
            ]);

            $isLaravel9 = version_compare($this->app->version(), '9.0.0', '>=');

            $this->publishes([
                __DIR__.'/../resources/lang' => $isLaravel9 ? lang_path('vendor/eatcard-companion') : resource_path('lang/vendor/eatcard-companion'),
            ], 'eatcardcompanion-translations');

            $this->publishes([
                __DIR__.'/../config/eatcardSms.php' => config_path('eatcardSms.php'),
            ], 'eatcardsms-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/eatcard-companion'),
            ], 'eatcardcompanion-views');

            $this->publishes([
                __DIR__.'/../migrations/create_sms_histories_table.php.stub' => $this->getMigrationFileName($filesystem),
            ], 'eatcardsms-migration');
        }

        Blade::directive('companionPrintTrans', function ($value) {
            return "<?php echo Weboccult\EatcardCompanion\Helpers\__companionPrintTrans($value); ?>";
        });

        Blade::directive('companionTrans', function ($value) {
            return "<?php echo Weboccult\EatcardCompanion\Helpers\__companionTrans($value); ?>";
        });

        Blade::directive('phpEncrypt', function ($value) {
            return "<?php echo Weboccult\EatcardCompanion\Helpers\phpEncrypt($value); ?>";
        });

        Blade::directive('companionGeneralHelper', function ($arguments) {
            list($function, $value) = explode(',', $arguments);
            $function = str_replace("'", '', $function);
            $value = eval('return '.$value.';');

            return "<?php echo Weboccult\EatcardCompanion\Helpers\\$function('$value'); ?>";
        });

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'eatcard-companion');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'eatcard-companion');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eatcardCompanion.php', 'eatcardCompanion');
        $this->mergeConfigFrom(__DIR__.'/../config/eatcardSms.php', 'eatcardSms');
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     *
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_sms_histories_table.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_sms_histories_table.php")
            ->first();
    }
}
