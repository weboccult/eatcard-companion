<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Actions;

use Exception;
use Throwable;
use Weboccult\EatcardCompanion\Models\Device;
use Weboccult\EatcardCompanion\Models\GeneralNotification;
use Weboccult\EatcardCompanion\Models\Message;
use Weboccult\EatcardCompanion\Models\StoreManager;
use Weboccult\EatcardCompanion\Models\StoreOwner;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use Weboccult\EatcardCompanion\Services\Facades\OneSignal;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;
use function Weboccult\EatcardCompanion\Helpers\__companionViews;
use function Weboccult\EatcardCompanion\Helpers\appDutchDate;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;
use function Weboccult\EatcardCompanion\Helpers\updateEmailCount;

/**
 * @mixin BaseWebhook
 *
 * @author Darshit Hedpara
 */
trait ReservationWebhookCommonActions
{
    /**
     * @return array|string[]|void
     */
    public function sendNotification()
    {
        if (($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_res_booking_notification))) {
            $person = ($this->fetchedStore->person > 1) ? $this->fetchedStore->person.' personen' : $this->fetchedStore->person.' persoon';
            if ($this->fetchedStore->achternaam || $this->fetchedStore->voornaam) {
                $name = '| '.$this->fetchedStore->voornaam.' '.$this->fetchedStore->achternaam;
            } else {
                $name = '';
            }
            $desc_title = $this->fetchedStore->store_name.': '.$name;
            $desc = appDutchDate($this->fetchedStore->getRawOriginal('res_date')).' | '.$this->fetchedStore->from_time.' | '.$person;
            $notificationData = [
                'type' => 'StoreBooking',
                'description' => $desc,
                'description_title' => $desc_title,
            ];
            $notificationData['additional_data'] = ['reservation_id' => $this->fetchedStore->id, 'status' => $this->fetchedStore->status];
            $notificationData['additional_data'] = json_encode($notificationData['additional_data']);
            try {
                $additional_data = json_decode($notificationData['additional_data']);
                if (isset($additional_data) && isset($additional_data->reservation_id)) {
                    $owners = StoreOwner::query()->where('store_id', $this->fetchedStore->id)->pluck('user_id')->toArray();
                    $managers = StoreManager::query()->where('store_id', $this->fetchedStore->id)->pluck('user_id')->toArray();

                    $currentOrdersUser = array_merge($owners, $managers);

                    if (! $currentOrdersUser) {
                        return ['status' => 'failed', 'message' => 'Reservation not attached with any user yet.!'];
                    }

                    $userIds = $currentOrdersUser;

                    $additional_data = [
                        'data' => json_decode($notificationData['additional_data'], true),
                    ];

                    $newNotification = GeneralNotification::create([
                        'type' => $notificationData['type'],
                        'notification' => $notificationData['description'],
                        'additional_data' => json_encode($additional_data),
                    ]);

                    $one_signal_user_devices_oids = [];
                    if ($newNotification) {
                        $newNotification->generalNotificationUsers()->attach($userIds);
                        $is_send_push = false;
                        $devices = Device::query()->whereIn('user_id', $userIds)->get();
                        if (count($devices) > 0) {
                            $one_signal_user_devices_oids = $devices->pluck('onesignal_id')->toArray();
                        }
                    }

                    $push_notification_data = [
                        'title' => 'Eatcard',
                        'text' => $notificationData['description'],
                        'type' => $notificationData['type'],
                        'description_title' => (! empty($notificationData['description_title'])) ? $notificationData['description_title'] : '',
                        'additional_data' => $notificationData['additional_data'],
                        'player_ids' => $one_signal_user_devices_oids,
                        'store_id' => $this->fetchedStore->id,
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

                    if ($is_send_push) {
                        return ['status' => 'success', 'message' => 'New notification created and send to all user.!'];
                    } else {
                        return ['status' => 'success', 'message' => 'New notification created. but failed to send.'];
                    }
                } else {
                    return ['status' => 'failed', 'message' => 'Reservation not found.!'];
                }
            } catch (\Exception $e) {
                return ['status' => 'failed', 'message' => 'Reservation not found.!'];
            }
        }
    }

    public function setLimitHoursIntoStoreData()
    {
        $time_limit = [
            105 => '1'.__companionTrans('reservation.hour').' 45'.__companionTrans('reservation.minutes'),
            120 => '2'.__companionTrans('reservation.hour'),
            135 => '2'.__companionTrans('reservation.hour').' 15'.__companionTrans('reservation.minutes'),
            150 => '2'.__companionTrans('reservation.hour').' 30'.__companionTrans('reservation.minutes'),
            165 => '2'.__companionTrans('reservation.hour').' 45'.__companionTrans('reservation.minutes'),
            180 => '3'.__companionTrans('reservation.hour'),
            195 => '3'.__companionTrans('reservation.hour').' 15'.__companionTrans('reservation.minutes'),
            210 => '3'.__companionTrans('reservation.hour').' 30'.__companionTrans('reservation.minutes'),
            225 => '3'.__companionTrans('reservation.hour').' 45'.__companionTrans('reservation.minutes'),
            240 => '4'.__companionTrans('reservation.hour'),
            255 => '4'.__companionTrans('reservation.hour').' 15'.__companionTrans('reservation.minutes'),
            270 => '4'.__companionTrans('reservation.hour').' 30'.__companionTrans('reservation.minutes'),
            285 => '4'.__companionTrans('reservation.hour').' 45'.__companionTrans('reservation.minutes'),
            300 => '5'.__companionTrans('reservation.hour'),
        ];
        $this->fetchedReservation->limit_hour = $time_limit[$this->fetchedReservation->meal->time_limit];
    }

    public function sendReservationStatusChangeEmail()
    {
        //send mail on auto approval of reservation status
        if ($this->fetchedReservation->email && filter_var($this->fetchedReservation->email, FILTER_VALIDATE_EMAIL)) {
            try {
                if ($this->fetchedReservation->status == 'approved') {
                    $view = 'email.reservation_approved';
                    $translatedSubject = __companionTrans('reservation.reservation_approved_email_subject');
                } elseif ($this->fetchedReservation->status == 'declined') {
                    $view = 'email.reservation_declined';
                    $translatedSubject = __companionTrans('reservation.reservation_declined_email_subject');
                } else {
                    $view = 'email.reservation_cancelled';
                    $translatedSubject = __companionTrans('reservation.reservation_cancelled_email_subject');
                }

                if ($this->fetchedReservation->thread_id) {
                    $messages = Message::query()->where(
                        'thread_id',
                        $this->fetchedReservation->thread_id
                    )
                        ->where('user_id', '!=', null)
                        ->orderBy('id', 'desc')->first();
                } else {
                    $messages = [];
                }

                $content = __companionViews($view, [
                                'storeRes' => $this->fetchedReservation,
                                'store' => $this->fetchedStore,
                                'resHis' => [],
                                'chat_link_url' => encrypt($this->fetchedReservation->store_id.'-'.$this->fetchedReservation->id.'-'.$this->fetchedReservation->user_id),
                                'messages' => $messages,
                            ])->render();
                eatcardEmail()
                    ->entityType('reservation_status_change')
                    ->entityId($this->fetchedReservation->id)
                    ->email($this->fetchedReservation->email)
                    ->mailType('Reservation status change')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Reservation status change create mail success', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Reservation status change create mail error', '#OrderId : '.$this->fetchedOrder->id, '#Email : '.$this->fetchedOrder->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }
    }

    public function sendNewReservationEmailToOwner()
    {
        if ($this->fetchedStore->store_email && filter_var($this->fetchedStore->store_email, FILTER_VALIDATE_EMAIL) && ($this->fetchedStore->is_notification) && (! $this->fetchedStore->notificationSetting || ($this->fetchedStore->notificationSetting && $this->fetchedStore->notificationSetting->is_res_booking_email))) {
            try {
                $content = __companionViews('email.booking-reservation-owner-notification', [
                                'storeRes' => $this->fetchedReservation,
                                'store' => $this->fetchedStore,
                                'resHis' => [],
                                'user' => [],
                            ])->render();
                $translatedSubject = __companionTrans('reservation.booking_notification_to_store_own_subject');
                eatcardEmail()
                    ->entityType('booking_notification_store_owner')
                    ->entityId($this->fetchedReservation->id)
                    ->email($this->fetchedStore->email)
                    ->mailType('Booking notification store owner')
                    ->mailFromName($this->fetchedStore->store_name)
                    ->subject($translatedSubject)
                    ->content($content)
                    ->dispatch();
                updateEmailCount('success');
                companionLogger('Booking notification store owner mail stored in queue success', '#ReservationId : '.$this->fetchedReservation->id, '#Email : '.$this->fetchedReservation->email, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            } catch (Exception | Throwable $e) {
                updateEmailCount('error');
                companionLogger('Booking notification store owner mail failed to save in queue', '#ReservationId : '.$this->fetchedReservation->id, '#Email : '.$this->fetchedReservation->email, '#Error : '.$e->getMessage(), '#ErrorLine : '.$e->getLine(), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
            }
        }
    }
}
