<?php

namespace Weboccult\EatcardCompanion\Commands;

use Illuminate\Console\Command;

/**
 * @description To publish the companion config file
 *
 * @author Darshit Hedpara
 */
class EatcardCompanionConfigPublishCommand extends Command
{
    public $signature = 'eatcardcompanion:publish';

    public $description = 'Publish eatcardcompanion config file';

    public function handle()
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
        $this->call('vendor:publish', ['--tag' => 'eatcardcompanion-config']);
    }
}
