<?php

namespace Weboccult\EatcardCompanion\Services\Common\Sms;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Notifications\Notification;

/**
 * Class SmsChannel.
 *
 * @author Darshit Hedpara
 */
class SmsChannel
{
    /**
     * @param $notifiable
     * @param Notification $notification
     *
     * @throws BindingResolutionException|\Throwable
     *
     * @return mixed
     */
    public function send($notifiable, Notification $notification)
    {
        /**
         * @psalm-suppress UndefinedMethod
         */
        $message = $notification->toSms($notifiable);
//        dd($message);

        $this->validate($message);
        $manager = app()->make('sms');

        if (! empty($message->getDriver())) {
            $manager->via($message->getDriver());
        }

        return $manager->send($message->getBody())
            ->type($message->getType())
            ->responsible($message->getResponsible())
            ->storeId($message->getStoreId())
            ->to($message->getRecipients())->dispatch();
    }

    /**
     * @param $message
     *
     * @throws \Throwable
     */
    private function validate($message)
    {
        $conditions = [
            'Invalid data for sms notification.' => ! is_a($message, Builder::class),
            'Message body could not be empty.' => empty($message->getBody()),
            'Message type could not be empty.' => empty($message->getType()),
            'Message recipient could not be empty.' => empty($message->getRecipients()),
        ];

        foreach ($conditions as $ex => $condition) {
            throw_if($condition, new Exception($ex));
        }
    }
}
