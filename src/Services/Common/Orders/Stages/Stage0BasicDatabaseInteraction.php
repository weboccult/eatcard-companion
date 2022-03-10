<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\Table;
use Weboccult\EatcardCompanion\Models\TakeawaySetting;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\phpDecrypt;

/**
 * @description Stag 0
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage0BasicDatabaseInteraction
{
    protected function setStoreData()
    {
        if (isset($this->payload['store_id']) && ! empty($this->payload['store_id'])) {
            $storeId = $this->payload['store_id'];
            $store = Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$storeId,
                STORE_CHANGE_BY_ID.$storeId,
                STORE_SETTING,
                TAKEAWAY_SETTING.$storeId,
            ])->remember('{eat-card}-store-with-settings-'.$storeId, CACHING_TIME, function () use ($storeId) {
                return Store::query()
                    ->with('storeSetting', 'multiSafe')->where('id', $storeId)->first();
            });
            if (! empty($store)) {
                $this->store = $store;
                $this->orderData['store_id'] = $this->store->id;
            }
        }
    }

    protected function setTableData()
    {
        if (isset($this->payload['table_id']) && ! empty($this->payload['table_id'])) {
            $tableId = $this->payload['table_id'];
            $storeId = $this->payload['store_id'];
            $table = Cache::tags([
                FLUSH_ALL,
                FLUSH_DINE_IN,
                EP_DINE_POST_ORDER,
                FLUSH_STORE_BY_ID.$storeId,
                TABLES,
                TABLE_BY_ID.$tableId,
            ])->remember('{eat-card}-table-by-id-'.$tableId, caching_time, function () use ($tableId) {
                return Table::findOrFail($tableId);
            });
            if (! empty($table)) {
                $this->table = $table;
                $this->orderData['table_id'] = $this->table->id;
            }
        }
    }

    protected function setTakeawaySettingData()
    {
        if ($this->system === SystemTypes::TAKEAWAY && isset($this->payload['store_id']) && ! empty($this->payload['store_id'])) {
            $storeId = $this->payload['store_id'];
            $takeawaySetting = Cache::tags([
                FLUSH_ALL,
                FLUSH_TAKEAWAY,
                EP_POST_ORDER,
                FLUSH_STORE_BY_ID.$storeId,
                TAKEAWAY_SETTING.$storeId,
            ])->remember('{eat-card}-takeaway-setting-'.$storeId, CACHING_TIME, function () use ($storeId) {
                return TakeawaySetting::query()->where('store_id', $storeId)->first();
            });
            if (! empty($takeawaySetting)) {
                $this->takeawaySetting = $takeawaySetting;
            }
        }
    }

    protected function setParentOrderData()
    {
        if (isset($this->payload['parent_order_id']) && ! empty($this->payload['parent_order_id'])) {
            $orderId = $this->payload['parent_order_id'];
            $parentOrder = Order::query()->with([
                'orderItems',
                'subOrders.subOrderItems',
            ])->where('id', $orderId)->first();
            if (! empty($parentOrder)) {
                $this->parentOrder = $parentOrder;
                $this->isSubOrder = true;
                $this->orderData['parent_order_id'] = $orderId;
            }
        }
    }

    protected function setDeviceData()
    {
        if (isset($this->payload['device_id']) && ! empty($this->payload['device_id']) && isset($this->payload['store_id']) && ! empty($this->payload['store_id'])) {
            $deviceId = $this->payload['device_id'];
            if ($this->system === SystemTypes::KIOSK) {
                $deviceId = phpDecrypt($this->payload['device_id']);
            }
            $storeId = $this->payload['store_id'];
            $device = Cache::tags([
                FLUSH_ALL,
                FLUSH_POS,
                FLUSH_STORE_BY_ID.$storeId,
                KIOSK_DEVICES,
            ])
                ->remember('{eat-card}-kiosk-device-with-code-'.$storeId.$deviceId, CACHING_TIME, function () use ($deviceId, $storeId) {
                    return KioskDevice::query()->where('pos_code', $deviceId)->where('store_id', $storeId)->first();
                });
            if (! empty($device)) {
                $this->device = $device;
                $this->orderData['kiosk_id'] = $device->id;
            }
        }
    }

    /**
     * @return void
     */
    protected function setReservationData()
    {
        if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id'])) {
            $reservationId = $this->isSubOrder ? $this->parentOrder->parent_id : $this->payload['reservation_id'];
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

        if ($this->system == SystemTypes::DINE_IN) {
//            $session_id = Session::get('dine-reservation-id-'.$this->store->id.'-'.$this->table->id);
            $session_id = $this->payload['reservation_id'] ?? 0;
            if (! empty($session_id)) {
                $reservation = StoreReservation::where('id', $session_id)->first();
                if (! empty($reservation)) {
                    $this->storeReservation = $reservation;
                }
            }
        }
    }
}
