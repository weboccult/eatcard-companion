<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\eatcardPrint;
use function Weboccult\EatcardCompanion\Helpers\getDutchDate;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;

/**
 * @description Stag 14
 *
 * @author Darshit Hedpara
 */
trait Stage14Notification
{
    protected function sendEmailLogic()
    {
        if ($this->system === SystemTypes::TAKEAWAY && ($this->createdOrder->method == 'cash' || $this->createdOrder->is_paylater_order == 1)) {
            /* User Email */
            if ($this->createdOrder->email && filter_var($this->createdOrder->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $content = eatcardPrint()
                       ->generator(PaidOrderGenerator::class)
                       ->method(PrintMethod::HTML)
                       ->type(PrintTypes::MAIN)
                       ->system(SystemTypes::TAKEAWAY)
                       ->payload([
                           'order_id'          => ''.$this->createdOrder->id,
                           'takeawayEmailType' => 'user',
                       ])
                       ->generate();
                    $translatedSubject = __companionTrans('takeaway.takeaway_order_user_mail_subject').': '.getDutchDate($this->createdOrder->order_date).' - '.$this->createdOrder->order_time.' - '.__companionTrans('general.'.$this->createdOrder->status);
                    eatcardEmail()
                       ->entityType('takeaway_user_email')
                       ->entityId($this->createdOrder->id)
                       ->email($this->createdOrder->email)
                       ->mailType('Takeaway user email')
                       ->mailFromName($this->store->store_name)
                       ->subject($translatedSubject)
                       ->content($content)
                       ->dispatch();
                    updateEmailCount('success');
                    companionLogger('Takeaway order create mail success', '#OrderId : '.$this->createdOrder->id, '#Email : '.$this->createdOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
                } catch (Exception | Throwable $e) {
                    updateEmailCount('error');
                    companionLogger('Takeaway order create mail error', '#OrderId : '.$this->createdOrder->id, '#Email : '.$this->createdOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
                }
            }

            /* Owner Email */
            if ($this->store->store_email && filter_var($this->store->store_email, FILTER_VALIDATE_EMAIL) && ($this->store->is_notification) && (! $this->store->notificationSetting || ($this->store->notificationSetting && $this->store->notificationSetting->is_takeaway_email))) {
                try {
                    $content = eatcardPrint()
                        ->generator(PaidOrderGenerator::class)
                        ->method(PrintMethod::HTML)
                        ->type(PrintTypes::MAIN)
                        ->system(SystemTypes::TAKEAWAY)
                        ->payload([
                            'order_id'          => ''.$this->createdOrder->id,
                            'takeawayEmailType' => 'owner',
                        ])
                        ->generate();
                    $translatedSubject = __companionTrans('takeaway.takeaway_order_owner_mail_sub_subject');
                    eatcardEmail()
                        ->entityType('takeaway_user_email')
                        ->entityId($this->createdOrder->id)
                        ->email($this->store->store_email)
                        ->mailType('Takeaway owner email')
                        ->mailFromName($this->store->store_name)
                        ->subject($translatedSubject)
                        ->content($content)
                        ->dispatch();
                    updateEmailCount('success');
                    companionLogger('Takeaway order create mail success', '#OrderId : '.$this->createdOrder->id, '#Email : '.$this->createdOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
                } catch (Exception | Throwable $e) {
                    updateEmailCount('error');
                    companionLogger('Takeaway order create mail error', '#OrderId : '.$this->createdOrder->id, '#Email : '.$this->createdOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
                }
            }
        }
    }

    protected function setSessionPaymentUpdate()
    {
        if ($this->system === SystemTypes::TAKEAWAY && ($this->createdOrder->method == 'cash' || $this->createdOrder->is_paylater_order == 1)) {
            Session::put('payment_update', [
                'status'   => 'paid',
                'order_id' => $this->createdOrder->id,
                // 'payment_status' => $order['status'],
                'message'  => __companionTrans('takeaway.order_success_msg', [
                    'time'       => $this->createdOrder->order_time,
                    'order_type' => __companionTrans('general.' . $this->createdOrder->order_type),
                ]),
            ]);
            Session::save();
        }
    }
}
