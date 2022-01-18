<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Weboccult\EatcardCompanion\Models\Device;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class OneSignal
{
    public array $deviceTypes = [
        'iOS',
        'ANDROID',
        'AMAZON',
        'WINDOWSPHONE (MPNS)',
        'CHROME APPS / EXTENSIONS',
        'CHROME WEB PUSH',
        'WINDOWSPHONE (WNS)',
        'SAFARI',
        'FIREFOX',
        'MACOS',
    ];

    private string $app_id;
    private string $app_rest_key;
    private string $api_url = 'https://onesignal.com/api/v1';

    public function __construct()
    {
        $this->app_id = env('ONE_SIGNAL_APP_ID');
        $this->app_rest_key = env('ONE_SIGNAL_REST_API_KEY');
    }

    /**
     * @param $onesignal_id
     *
     * @return false|mixed
     */
    public function createDevice($onesignal_id): bool
    {
        $url = $this->api_url.'/players/'.$onesignal_id.'?app_id='.$this->app_id;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response);
            $device = Device::create([
                'user_id'      => auth()->id(),
                'onesignal_id' => $data->id,
                'device_name'  => $data->device_model,
                'platform'     => $this->deviceTypes[$data->device_type] ?? 'UNKNOWN',
            ]);
            companionLogger('[ Onesignal | New Device created ]', date('Y-m-d H:i:s'), json_encode($device), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
            Device::query()->where('onesignal_id', $onesignal_id)->where('id', '!=', $device->id)->forceDelete();

            return $device;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $notification
     *
     * @return bool|void
     */
    public function sendPushNotification($notification)
    {
        $content = [
            'en' => $notification['text'],
        ];
        if ($notification['description_title'] != '') {
            $description_title = [
                'en' => $notification['description_title'],
            ];
        }
        $totalPendingNotificationCount = $this->getTotalPendingReservation($notification['store_id']);
        $fields = [
            'app_id'             => $this->app_id,
            'include_player_ids' => $notification['player_ids'],
            'ios_badgeType'      => 'SetTo',
            'ios_badgeCount'     => ($totalPendingNotificationCount != 0) ? $totalPendingNotificationCount + 1 : $totalPendingNotificationCount,
            'data'               => [
                'type'            => $notification['type'],
                'additional_data' => $notification['additional_data'],
            ],
            'contents'           => $content,
            'headings'           => $description_title ?? '',
        ];
        $fields = json_encode($fields);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api_url.'/notifications');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic '.$this->app_rest_key,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response);
            if ($data->id == '' || $data->recipients == 0) {
                if (! empty($data->errors) && ! empty($data->errors->invalid_player_ids)) {
                    companionLogger('[Onesignal push notification | errors | INVALID PLAYERS IDS ]', date('Y-m-d H:i:s'), json_encode($data->errors->invalid_player_ids), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));

                    return false;
                }
            } else {
                companionLogger('[Onesignal push notification send | '.$notification['text'].' ]', json_encode($notification['player_ids']), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));

                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    public function getTotalPendingReservation($storeId): int
    {
        try {
            $pending_count = StoreReservation::query()
                ->where('store_id', $storeId)
                ->where('status', 'pending')
                ->count();
        } catch (Exception $e) {
            $pending_count = 0;
        }

        return $pending_count;
    }
}
