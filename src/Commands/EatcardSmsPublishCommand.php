<?php

namespace Weboccult\EatcardCompanion\Commands;

use Illuminate\Console\Command;

/**
 * @description To publish the companion sms config file
 *
 * @author Darshit Hedpara
 */
class EatcardSmsPublishCommand extends Command
{
    public $signature = 'eatcardsms:publish {--type=}';

    public $description = 'Publish eatcardsms config / migration file';

    public function handle()
    {
        $publishType = $this->option('type');
        if (empty($publishType)) {
            $this->error('Please provide publish type.! ( config / migration ) ?');

            return;
        }

        if ($publishType == 'config') {
            $answer = $this->ask('Are you sure, you want to publish a config file ? y/N', 'N');
            if ($answer == 'n' || $answer == 'N') {
                $this->error('You have cancelled the operation.!');

                return;
            }
            $this->configPublish();
        } elseif ($publishType == 'migration') {
            $answer = $this->ask('Are you sure, you want to publish a migration file ? y/N', 'N');
            if ($answer == 'n' || $answer == 'N') {
                $this->error('You have cancelled the operation.!');

                return;
            }
            $this->migrationPublish();
        } else {
            $this->error('Publish type is not supported, Available types ( config / migration ).!');
        }
    }

    private function configPublish()
    {
        if (file_exists(config_path('eatcardSms.php'))) {
            $this->error('eatcardSms.php is already exist. config file publish failed.!');
            $answer = $this->ask('Are you sure you want to replace the eatcardSms config file ? [y/N]', 'N');
            if ($answer == 'y' || $answer == 'Y') {
                copy(__DIR__.'/../../config/eatcardSms.php', config_path('eatcardSms.php'));
                $this->info('eatcardSms Config file published successfully.!');
            }

            return;
        }
        $this->call('vendor:publish', ['--tag' => 'eatcardsms-config']);
    }

    private function migrationPublish()
    {
        $this->call('vendor:publish', ['--force'=> null, '--tag' => 'eatcardsms-migration']);
    }
}
