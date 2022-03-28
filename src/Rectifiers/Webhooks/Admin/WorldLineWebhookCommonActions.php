<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Admin;

use Exception;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis as LRedis;
use Throwable;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\eatcardPrint;
use function Weboccult\EatcardCompanion\Helpers\getDutchDate;
use function Weboccult\EatcardCompanion\Helpers\sendAppNotificationHelper;
use function Weboccult\EatcardCompanion\Helpers\sendWebNotification;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;

/**
 * @mixin BaseWebhook
 *
 * @author Darshit Hedpara
 */
trait WorldLineWebhookCommonActions
{
    /**
     * @return array|void
     */
    public function sendNotifications()
    {
        $orderArray = $this->fetchedOrder->toArray();
        $is_notification = 0;
        if (($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting->is_dine_in_notification))) {
            $is_notification = 1;
            $data = sendAppNotificationHelper($orderArray, $this->fetchedStore);
            if (isset($data['exception'])) {
                return $data;
            }
        }
        $takeaway_data = [
            'orderDate'       => $this->fetchedOrder->order_date,
            'is_notification' => $is_notification,
        ];
        $socket_data = sendWebNotification($this->fetchedStore, $this->fetchedOrder, $takeaway_data);
        $socket_data['type'] = 'kiosk';
        $socket_data['kiosk_id'] = $this->fetchedOrder->kiosk->id;
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }
    }

    public function sendEmails()
    {
        if ($this->fetchedOrder->email && filter_var($this->fetchedOrder->email, FILTER_VALIDATE_EMAIL)) {
            try {
                $content = eatcardPrint()
                    ->generator(PaidOrderGenerator::class)
                    ->method(PrintMethod::HTML)
                    ->type(PrintTypes::MAIN)
                    ->system(SystemTypes::TAKEAWAY)
                    ->payload([
                        'order_id'          => ''.$this->fetchedOrder->id,
                        'takeawayEmailType' => 'user',
                    ])
                    ->generate();
                $translatedSubject = __companionTrans('takeaway.takeaway_order_user_mail_subject').': '.getDutchDate($this->fetchedOrder->order_date).' - '.$this->fetchedOrder->order_time.' - '.__companionTrans('general.'.$this->fetchedOrder->status);
                eatcardEmail()
                    ->entityType('takeaway_user_email')
                    ->entityId($this->fetchedOrder->id)
                    ->email($this->fetchedOrder->email)
                    ->mailType('Takeaway user email')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Takeaway order create mail success', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Takeaway order create mail error', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }

        if ($this->fetchedStore->store_email && filter_var($this->fetchedStore->store_email, FILTER_VALIDATE_EMAIL) && ($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_takeaway_email))) {
            try {
                $content = eatcardPrint()
                    ->generator(PaidOrderGenerator::class)
                    ->method(PrintMethod::HTML)
                    ->type(PrintTypes::MAIN)
                    ->system(SystemTypes::TAKEAWAY)
                    ->payload([
                        'order_id'          => ''.$this->fetchedOrder->id,
                        'takeawayEmailType' => 'owner',
                    ])
                    ->generate();
                $translatedSubject = __companionTrans('takeaway.takeaway_order_owner_mail_sub_subject');
                eatcardEmail()
                    ->entityType('takeaway_user_email')
                    ->entityId($this->fetchedOrder->id)
                    ->email($this->fetchedStore->store_email)
                    ->mailType('Takeaway owner email')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Takeaway order create mail success', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Takeaway order create mail error', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }
    }

    public function sendPrintJsonToSQS()
    {
        // Todo : get print JSON data from EatcardPrint Service
        $printRes = [];
        if (! empty($printRes)) {
            config([
                'queue.connections.sqs.region' => $this->fetchedStore->sqs->sqs_region,
                'queue.connections.sqs.queue'  => $this->fetchedStore->sqs->sqs_queue_name,
                'queue.connections.sqs.prefix' => $this->fetchedStore->sqs->sqs_url,
            ]);
            Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->fetchedStore->sqs->sqs_queue_name);
        }
    }
}
