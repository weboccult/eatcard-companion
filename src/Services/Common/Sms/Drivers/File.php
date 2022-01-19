<?php

namespace Weboccult\EatcardCompanion\Services\Common\Sms\Drivers;

use Illuminate\Support\Facades\Log;
use Weboccult\EatcardCompanion\Services\Common\Sms\Driver;

/**
 * Class File.
 *
 * @author Darshit Hedpara
 */
class File extends Driver
{
    protected array $settings;

    /**
     * Driver constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = null;
    }

    /**
     * @return bool
     */
    public function send(): bool
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            $result['actual_response'] = 'This is dummy response because this is file driver';
            $result['formatted_response'] = [
                'status'          => 'success',
                'is_sent'         => 1,
                'is_error'        => 0,
                'resend_required' => 0,
                'resend_count'    => $this->reTryCount,
            ];
            $response->put($recipient, $result);
        }
        $logLevel = empty(data_get($this->settings, 'log_level')) ? 'info' : $this->settings['log_level'];
        Log::$logLevel('SMS Response : '.PHP_EOL.'-------------------------------'.PHP_EOL.json_encode($response, JSON_PRETTY_PRINT).PHP_EOL.'-------------------------------'.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        $this->keepHistory($response);

        return true;
    }
}
