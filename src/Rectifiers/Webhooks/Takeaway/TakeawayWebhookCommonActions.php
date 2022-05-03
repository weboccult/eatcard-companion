<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Takeaway;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Redis as LRedis;
use Throwable;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\GiftPurchaseOrder;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use Weboccult\EatcardCompanion\Services\Facades\EatcardPrint;
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
trait TakeawayWebhookCommonActions
{
    public function applyCouponLogic()
    {
        $coupon_purchase = GiftPurchaseOrder::query()->where('id', $this->fetchedOrder->gift_purchase_id)->first();
        if ($coupon_purchase) {
            if ($coupon_purchase->is_multi_usage == 1) {
                $coupon_purchase->update(['remaining_price' => $coupon_purchase->remaining_price - $this->fetchedOrder->coupon_price]);
            } else {
                $coupon_purchase->update(['remaining_price' => 0]);
            }
        }
    }

    /**
     * @return mixed|void
     */
    public function sendNotifications()
    {
        $orderArray = $this->fetchedOrder->toArray();
        $is_notification = 0;
        if (($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting->is_takeaway_notification))) {
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
        if ($socket_data) {
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode($socket_data));
        }
    }

    public function sendTakeawayUserEmail()
    {
        /*send confirmation mail to user after order status updated to paid or canceled*/
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
    }

    public function sendTakeawayOwnerEmail()
    {
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
        $printRes = [];
//        /*Find order item dfference with current time*/
//        $current_time = Carbon::now();
//        $order_time_difference = $current_time->diffInMinutes(Carbon::now()->parse($this->fetchedOrder->order_time));

        if (($this->fetchedStore->future_order_print_status == 0 || ($this->fetchedOrder->order_date == Carbon::now()->format('Y-m-d') /*&& $order_time_difference <= $this->fetchedStore->future_order_print_time*/))) {
            $printRes = EatcardPrint::generator(PaidOrderGenerator::class)
               ->method(PrintMethod::SQS)
               ->type(PrintTypes::DEFAULT)
               ->system(SystemTypes::TAKEAWAY)
               ->payload(['order_id' => ''.$this->fetchedOrder->id])
               ->generate();
        } else {
            Order::query()->where('id', $this->fetchedOrder->id)->update(['is_future_order_print_pending' => 1]);
        }

//        $printRes = EatcardPrint::generator(PaidOrderGenerator::class)
//            ->method(PrintMethod::SQS)
//            ->type(PrintTypes::DEFAULT)
//            ->system(SystemTypes::TAKEAWAY)
//            ->payload(['order_id' => ''.$this->fetchedOrder->id])
//            ->generate();
        if (! empty($printRes)) {
            config([
                'queue.connections.sqs.region' => $this->fetchedStore->sqs->sqs_region,
                'queue.connections.sqs.queue'  => $this->fetchedStore->sqs->sqs_queue_name,
                'queue.connections.sqs.prefix' => $this->fetchedStore->sqs->sqs_url,
            ]);
            \Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->fetchedStore->sqs->sqs_queue_name);
        }
    }
}
