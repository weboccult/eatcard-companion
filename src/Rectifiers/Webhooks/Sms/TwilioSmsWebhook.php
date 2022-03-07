<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Sms;

use Twilio\Rest\Client;
use Weboccult\EatcardCompanion\Models\SmsHistory;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class TwilioSmsWebhook extends  BaseWebhook
{
    /**
     * @return void
     */
    public function handle(): void
    {
        companionLogger('SMS Webhook : ', PHP_EOL,
                '-------------------------------', PHP_EOL,
                json_encode($this->payload, JSON_PRETTY_PRINT), PHP_EOL,
                '-------------------------------',
                'IP address : '.request()->ip(),
                'Browser : '. request()->header('User-Agent')
        );
        try {
            $history = SmsHistory::query()->where('sid', $this->payload['MessageSid'])->firstOrFail();
            $settings = config('sms.drivers.twilio');
            $client = new Client(data_get($settings, 'sid'), data_get($settings, 'token'));
            $message = $client->messages($history->sid)->fetch();
            $messageData = $message->toArray();
            $price = $messageData['price'] ?? null;
            $updateData = [
                'status' => $this->payload['MessageStatus']
            ];
            if ($price) {
                $updateData['price'] = (float)$price;
            }
            $history->update($updateData);
        }
        catch (\Exception $e) {
            companionLogger('SMS History not found.!',
                'SID : '. $this->payload['MessageSid'], 'Error'. $e->getMessage(),
                'IP address : '.request()->ip(),
                'Browser : '. request()->header('User-Agent')
            );
        }
    }
}
