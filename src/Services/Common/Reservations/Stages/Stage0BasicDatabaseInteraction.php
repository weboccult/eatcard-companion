<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\StoreSlot;
use Weboccult\EatcardCompanion\Models\StoreSlotModified;
use Weboccult\EatcardCompanion\Models\Table;
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
        $posCode = $this->payload['pos_code'] ?? 0;
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            $device = Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$this->store->id,
                KIOSK_DEVICES,
            ])
                ->remember('{eat-card}-companion-kiosk-tickets-device-with-kiosk-id-'.$this->store->id.$deviceId, CACHING_TIME, function () use ($deviceId) {
                    return KioskDevice::query()->where('id', $deviceId)->where('store_id', $this->store->id)->first();
                });
        }

        if ($this->system == SystemTypes::POS) {
            $device = Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$this->store->id,
                KIOSK_DEVICES,
            ])
                ->remember('{eat-card}-companion-kiosk-tickets-device-with-pos-code-'.$this->store->id.$posCode, CACHING_TIME, function () use ($posCode) {
                    return KioskDevice::query()->where('pos_code', $posCode)->where('store_id', $this->store->id)->first();
                });
        }

        if (! empty($device)) {
            $this->device = $device;
            $this->reservationData['kiosk_id'] = $device->id;
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

        if (isset($this->store->reservation_tickets_data) && ! empty($this->store->reservation_tickets_data)) {
            $this->store->reservation_tickets_data = json_decode($this->store->reservation_tickets_data, true);
            $this->allowNowSlot = ! empty($this->store->reservation_tickets_data['allow_booking_for_current_time_only'] ?? 0);
        }

        if ($this->payload['res_date'] != Carbon::now()->format('Y-m-d')) {
            $this->allowNowSlot = false;
        }

        if ($this->allowNowSlot) {
            $this->slot = (object) ([
                            'id' => 0,
                            'store_id' => $this->store->id,
                            'from_time' => Carbon::now()->format('G:i'),
                            'max_entries' => 'Unlimited',
                            'meal_id' => $this->meal->id,
                            'store_weekdays_id' => null,
                            'is_slot_disabled' => 0,
                            'meal_group_id' => null,

                        ]);
            $this->reservationData['slot_id'] = null;
        } elseif (! empty($slotId)) {
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

    protected function setTableIds()
    {
        $tableIds = $this->payload['table_ids'] ?? [];
        if (! empty($tableIds)) {
            $this->table = Table::query()->whereIn('id', $tableIds);
        }
    }
}
