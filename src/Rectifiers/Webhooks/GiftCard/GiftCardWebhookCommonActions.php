<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\GiftCard;

use Exception;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;
use Weboccult\EatcardCompanion\Models\Device;
use Weboccult\EatcardCompanion\Models\GeneralNotification;
use Illuminate\Support\Facades\Redis as LRedis;
use Weboccult\EatcardCompanion\Models\Notification;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\OneSignal;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\__companionViews;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\getDutchDate;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;

/**
 * @mixin BaseWebhook
 *
 * @author Darshit Hedpara
 */
trait GiftCardWebhookCommonActions
{
    /**
     * @return string
     */
    public function generateQRCode(): string
    {
        $image = QrCode::format('png')->size(500)->generate($this->fetchedGiftPurchaseOrder->qr_code);
        $qr_image = '/store/'.$this->fetchedStore->store_slug.'/qr-codes/'.base64_encode($this->fetchedStore->id).'-'.time().'.png';
        Storage::disk('s3')->put($qr_image, $image);

        return config('eatcardCompanion.aws_url').'/'.ltrim($qr_image, '/');
    }

    /**
     * @return array|void
     */
    public function sendAppNotification()
    {
        $name = '';
        if ($this->fetchedGiftPurchaseOrder->first_name || $this->fetchedGiftPurchaseOrder->last_name) {
            $name = '| '.$this->fetchedGiftPurchaseOrder->first_name.' '.$this->fetchedGiftPurchaseOrder->last_name;
        }
        $desc_title = $this->fetchedStore->store_name.' '.$name;
        $desc = $this->fetchedGiftPurchaseOrder->date.' | '.$this->fetchedGiftPurchaseOrder->order_time.' | €'.changePriceFormat($this->fetchedGiftPurchaseOrder->total_price);
        $notificationData = [
            'type' => 'takeaway',
            'description' => $desc,
            'description_title' => $desc_title,
        ];
        $notificationData['additional_data'] = ['order_id' => $this->fetchedGiftPurchaseOrder->id, 'date' => $this->fetchedGiftPurchaseOrder->date];
        $notificationData['additional_data'] = json_encode($notificationData['additional_data']);
        try {
            $newNotification = GeneralNotification::create([
                'type' => $notificationData['type'],
                'notification' => $notificationData['description'],
                'additional_data' => $notificationData['additional_data'],
            ]);

            $userIds = [];
            if ($this->fetchedStore->store_manager) {
                $userIds[] = $this->fetchedStore->store_manager->user_id;
            }
            if ($this->fetchedStore->store_owner) {
                $userIds[] = $this->fetchedStore->store_owner->user_id;
            }
            $one_signal_user_devices_oids = [];
            if ($newNotification && $userIds) {
                $newNotification->users()->attach($userIds);
                $devices = Device::query()->whereIn('user_id', $userIds)->get();
                if (count($devices) > 0) {
                    $one_signal_user_devices_oids = $devices->pluck('onesignal_id')->toArray();
                }
                $push_notification_data = [
                    'title' => 'Eatcard',
                    'text' => $notificationData['description'],
                    'type' => $notificationData['type'],
                    'description_title' => (! empty($notificationData['description_title'])) ? $notificationData['description_title'] : '',
                    'additional_data' => $notificationData['additional_data'],
                    'player_ids' => $one_signal_user_devices_oids,
                    'store_id' => $this->fetchedGiftPurchaseOrder->store_id,
                ];
                try {
                    $is_send_push = OneSignal::sendPushNotification($push_notification_data);
                    if ($is_send_push) {
                        $newNotification->users()->detach($userIds);
                        $newNotification->delete();
                    }
                } catch (\Exception $exception) {
                    return ['status' => 'failed', 'message' => json_encode($exception)];
                }
            }
        } catch (\Exception $e) {
            companionLogger('reservation status change push notification error: => '.$e->getFile().' '.$e->getMessage().' | '.$e->getLine().', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        }
    }

    /**
     * @param $store
     * @param $order
     * @param $data
     *
     * @return void
     */
    public function sendWebNotification($store, $order, $data)
    {
        try {
            $notification = Notification::create([
                'store_id' => $order->store_id,
                'notification' => __('messages.new_order_notification', [
                    'order_id' => $order->order_id,
                    'username' => $order->full_name,
                ]),
                'type' => 'gift-card',
                'additional_data' => json_encode([
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'date' => $order->date,
                    'order_time' => $order->time,
                    'total_price' => $order->total_price,
                    'order_type'      => 'gift-card',
                    'full_name' => $order->full_name,
                    'contact_no' => $order->contact_no,
                    'date' => $data['orderDate'],
                    'dutch_date' => getDutchDate($data['orderDate']),
                    'is_auto_print' => (/*$store->app_pos_print &&*/$store->is_auto_print_takeaway),
                    'is_notification' => ($data['is_notification']),
                ]),
                'read_at' => /*(!$data['is_notification']) ? Carbon::now()->format('Y-m-d H:i:s') : */null,
            ]);
            //redis option
            $redis = LRedis::connection();
            $redis->publish('new_order', json_encode([
                'store_id' => $order->store_id,
                'notification_id' => $notification->id,
                'additional_data' => $notification->additional_data,
            ]));
        } catch (\Exception $e) {
            companionLogger('giftcard - web notification error'.$e->getMessage().', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        }
    }

    /**
     * @param $qrImage
     *
     * @return void
     */
    public function sendUserEmails($qrImage)
    {
        /*send confirmation mail to user after order status updated to paid or canceled*/
        if ($this->fetchedGiftPurchaseOrder->is_friend_send == 1 && $this->fetchedGiftPurchaseOrder->is_specific_date == 0 && $this->fetchedGiftPurchaseOrder->email && filter_var($this->fetchedGiftPurchaseOrder->friend_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $content = __companionViews('gift-card.purchase-friend', [
                                'storeRes' => $this->fetchedReservation,
                                'store' => $this->fetchedStore,
                                'qr_image' => $qrImage,
                                'gift_card' => $this->fetchedGiftPurchaseOrder->giftCard ?? '',
                            ])->render();
                $translatedSubject = __companionTrans('giftcard.gift_purchase_friend_subject').': '.getDutchDate($this->fetchedGiftPurchaseOrder->date).' - '.$this->fetchedGiftPurchaseOrder->time.' - '.__companionTrans('general.'.$this->fetchedGiftPurchaseOrder->status);
                eatcardEmail()
                    ->entityType('giftcard_purchase_friend')
                    ->entityId($this->fetchedGiftPurchaseOrder->id)
                    ->email($this->fetchedGiftPurchaseOrder->friend_email)
                    ->mailType('Giftcard Purchase friend email')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Mollie | Gift purchase for friend create mail success', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Mollie | Gift purchase for friend mail error', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }

        try {
            if ($this->fetchedGiftPurchaseOrder->email && filter_var($this->fetchedGiftPurchaseOrder->email, FILTER_VALIDATE_EMAIL)) {
                $content = __companionViews('gift-card.purchase-user', [
                    'storeRes'  => $this->fetchedReservation,
                    'store'     => $this->fetchedStore,
                    'qr_image'  => $qrImage,
                    'gift_card' => $this->fetchedGiftPurchaseOrder->giftCard ?? '',
                ])->render();
                $translatedSubject = __companionTrans('giftcard.gift_purchase_user_subject').': '.getDutchDate($this->fetchedGiftPurchaseOrder->date).' - '.$this->fetchedGiftPurchaseOrder->time.' - '.__companionTrans('general.'.$this->fetchedGiftPurchaseOrder->status);
                eatcardEmail()
                    ->entityType('giftcard_purchase_user')
                    ->entityId($this->fetchedGiftPurchaseOrder->id)
                    ->email($this->fetchedGiftPurchaseOrder->email)
                    ->mailType('Giftcard Purchase User email')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Mollie | Gift purchase User create mail success', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        } catch (Exception | Throwable $e) {
            updateEmailCount('error');
            companionLogger('Mollie | Gift purchase User mail error', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
    }

    /**
     * @param $qrImage
     *
     * @return void
     */
    public function sendOwnerEmail($qrImage)
    {
        try {
            if ($this->fetchedStore->store_email && filter_var($this->fetchedStore->store_email, FILTER_VALIDATE_EMAIL) && ($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_takeaway_email))) {
                $content = __companionViews('gift-card.purchase-owner', [
                    'storeRes'  => $this->fetchedReservation,
                    'store'     => $this->fetchedStore,
                    'qr_image'  => $qrImage,
                    'gift_card' => $this->fetchedGiftPurchaseOrder->giftCard ?? '',
                ])->render();
                $translatedSubject = __companionTrans('giftcard.gift_purchase_owner_subject').': '.getDutchDate($this->fetchedGiftPurchaseOrder->date).' - '.$this->fetchedGiftPurchaseOrder->time.' - '.__companionTrans('general.'.$this->fetchedGiftPurchaseOrder->status);
                eatcardEmail()
                    ->entityType('giftcard_purchase_admin')
                    ->entityId($this->fetchedGiftPurchaseOrder->id)
                    ->email($this->fetchedStore->store_email)
                    ->mailType('Giftcard Purchase Admin email')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Mollie | Gift purchase User create mail success', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        } catch (Exception | Throwable $e) {
            updateEmailCount('error');
            companionLogger('Mollie | Gift purchase User mail error', '#OrderId : '.$this->fetchedGiftPurchaseOrder->id, '#Email : '.$this->fetchedGiftPurchaseOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        }
    }
}
