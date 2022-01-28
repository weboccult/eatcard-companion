<?php

namespace Weboccult\EatcardCompanion\Commands;

use Illuminate\Console\Command;

/**
 * @description To publish the companion config file
 *
 * @author Darshit Hedpara
 */
class EatcardCompanionPublishCommand extends Command
{
    public $signature = 'eatcardcompanion:publish {--type=}';

    public $description = 'Publish eatcardcompanion config / translations file';

    public function handle()
    {
        $publishType = $this->option('type');
        if (empty($publishType)) {
            $this->error('Please provide publish type.! ( config / translations ) ?');

            return;
        }

        if ($publishType == 'config') {
            $answer = $this->ask('Are you sure, you want to publish a config file ? y/N', 'N');
            if ($answer == 'n' || $answer == 'N') {
                $this->error('You have cancelled the operation.!');

                return;
            }
            $this->configPublish();
        } elseif ($publishType == 'translations') {
            $answer = $this->ask('Are you sure, you want to publish a translations file ? y/N', 'N');
            if ($answer == 'n' || $answer == 'N') {
                $this->error('You have cancelled the operation.!');

                return;
            }
            $this->translationPublish();
        } elseif ($publishType == 'views') {
            $answer = $this->ask('Are you sure, you want to publish a view files ? y/N', 'N');
            if ($answer == 'n' || $answer == 'N') {
                $this->error('You have cancelled the operation.!');

                return;
            }
            $this->viewPublish();
        } else {
            $this->error('Publish type is not supported, Available types ( config / translations ).!');
        }
    }

    private function configPublish()
    {
        if (file_exists(config_path('eatcardCompanion.php'))) {
            $this->error('eatcardCompanion.php is already exist. config file publish failed.!');
            $answer = $this->ask('Are you sure you want to replace the eatcardcompanion config file ? [y/N]', 'N');
            if ($answer == 'y' || $answer == 'Y') {
                copy(__DIR__.'/../../config/eatcardCompanion.php', config_path('eatcardCompanion.php'));
                $this->info('Config file published successfully.!');
            }

            return;
        }
        $this->call('vendor:publish', ['--force'=> null, '--tag' => 'eatcardcompanion-config']);
    }

    private function translationPublish()
    {
        $this->call('vendor:publish', ['--force'=> null, '--tag' => 'eatcardcompanion-translations']);
    }

    private function viewPublish()
    {
        $this->call('vendor:publish', ['--force'=> null, '--tag' => 'eatcardcompanion-views']);
    }
}
