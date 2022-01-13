<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

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
                return Store::query()->with('storeSetting')->where('id', $storeId)->first();
            });
            if (! empty($store)) {
                $this->store = $store;
                $this->orderData['store_id'] = $this->store->id;
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
    }
}
