<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\StoreSlot;
use Weboccult\EatcardCompanion\Models\StoreSlotModified;
use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;

/**
 * @description Stag 0
 * @mixin BaseProcessor
 */
trait Stage0BasicDatabaseInteraction
{
    protected function setStoreData()
    {
        $storeId = $this->payload['store_id'] ?? 0;
        if (! empty($storeId)) {
            $store = /*Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$storeId,
                STORE_CHANGE_BY_ID.$storeId,
                STORE_SETTING,
                TAKEAWAY_SETTING.$storeId,
            ])->remember('{eat-card}-companion-store-with-settings-'.$storeId, CACHING_TIME, function () use ($storeId) {
                return*/ Store::query()
                    ->with('storeSetting', 'multiSafe', 'storeButler', 'notificationSetting', 'store_manager', 'store_owner', 'sqs')->where('id', $storeId)->first();
            /*});*/
            if (! empty($store)) {
                $this->store = $store;
                $this->reservationData['store_id'] = $this->store->id;
            }
        }
    }

    protected function setDeviceData()
    {
        $deviceId = $this->payload['device_id'] ?? 0;
        if (! empty($deviceId)) {
            $device = Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$this->store->id,
                KIOSK_DEVICES,
            ])
                ->remember('{eat-card}-companion-kiosk-tickets-device-with-code-'.$this->store->id.$deviceId, CACHING_TIME, function () use ($deviceId) {
                    return KioskDevice::query()->where('id', $deviceId)->where('store_id', $this->store->id)->first();
                });
            if (! empty($device)) {
                $this->device = $device;
                $this->reservationData['kiosk_id'] = $device->id;
                // Todo : need to add migration for kiosk_id in store_reservation table
            }
        }
    }

    protected function setMealData()
    {
        $mealType = $this->payload['meal_type'] ?? '';
        if (! empty($mealType)) {
            $meal = Meal::query()->where('id', $mealType)->first();
            if (! empty($meal)) {
                $this->meal = $meal;
                $this->reservationData['meal_type'] = $mealType;
            }
        }
    }

    protected function setSlotData()
    {
        $this->slotType = $this->payload['data_model'] ?? '';
        $slotId = $this->payload['slot_id'] ?? '';
        $slot = [];

        if (! empty($slotId)) {
            if ($this->slotType == 'StoreSlot') {
                $slot = StoreSlot::query()->where('id', $slotId)->first();
            }

            if ($this->slotType == 'StoreSlotModified') {
                $slot = StoreSlotModified::query()->where('id', $slotId)->first();
            }

            if (! empty($slot)) {
                $this->slot = $slot;
                $this->reservationData['slot_id'] = $slotId;
            }
        }
    }

    /**
     * @return void
     */
    protected function setReservationData()
    {
        $reservationId = $this->payload['reservation_id'] ?? 0;
        if (! empty($reservationId)) {
            $reservation = StoreReservation::with([
                'dineInPrice' => function ($q1) {
                    $q1->withTrashed();
                },
                'tables.table',
            ])->where('id', $reservationId)->first();
            if (! empty($reservation)) {
                $this->storeReservation = $reservation;
            }
        }

        if (! empty($reservation)) {
            $this->storeReservation = $reservation;
        }
    }
}
