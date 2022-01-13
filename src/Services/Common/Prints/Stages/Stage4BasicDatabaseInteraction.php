<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Exceptions\OrderNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 4
 * @mixin BaseGenerator
 */
trait Stage4BasicDatabaseInteraction
{
    protected function setSubOrderData()
    {
        if ($this->orderType != OrderTypes::SUB) {
            return;
        }

        if (empty($this->subOrderId)) {
            companionLogger('Eatcard companion : No order id found for get order details');

            return;
        }

        $subOrder = '';
    }

    protected function setOrderData()
    {
        if ($this->orderType != OrderTypes::PAID) {
            return;
        }

        if (empty($this->orderId)) {
            companionLogger('Eatcard companion : No order id found for get order details');

            return;
        }
        $order = Order::with([
            'orderItems' => function ($q1) {
                $q1->with([
                    'product' => function ($q2) {
                        $q2->withTrashed()->with([
                            'printers',
                            'category' => function ($q3) {
                                $q3->withTrashed();
                            },
                        ]);
                    },
                ]);
            },
        ])->where('id', $this->orderId)->first();
        companionLogger('Eatcard companion : Order details : ', $order);
        //if no details found in order then check on history table
        if (empty($order)) {
            companionLogger('Eatcard companion : Order details -1 : ', $order);
            $order = OrderHistory::with([
                'orderItems' => function ($q1) {
                    $q1->with([
                        'product' => function ($q2) {
                            $q2->withTrashed()->with([
                                'printers',
                                'category' => function ($q3) {
                                    $q3->withTrashed();
                                },
                            ]);
                        },
                    ]);
                },
            ])->where('id', $this->orderId)->first();
        }

        //validate as per order type
        if (empty($order) && in_array($this->orderType, [OrderTypes::PAID])) {
            companionLogger('Eatcard companion : No order Data found for get order details');
            throw new OrderNotFoundException();
        }
        $this->order = $order->toArray();
        companionLogger('Eatcard companion : Order with details', $this->order);
    }

    protected function setReservationData()
    {
        //if order data is set then take this reservation
        if (! empty($this->order) && ! empty($this->order['parent_id'])) {
            $this->reservationId = $this->order['parent_id'];
        }
        if (empty($this->reservationId)) {
            companionLogger('Eatcard companion : No reservation found for get reservation');

            return;
        }

        $reservation = StoreReservation::with(['tables2'])->where('id', $this->reservationId)->first();

        //validate as per order type
        if (empty($reservation) && $this->orderType == OrderTypes::RUNNING) {
            throw new StoreReservationEmptyException();
        }

        companionLogger('--Eatcard companion reservation details : ', $reservation);
        $this->reservation = $reservation;
    }

    protected function setSaveOrderData()
    {
        if ($this->orderType != OrderTypes::SAVE) {
            return;
        }
    }

    protected function setStoreData()
    {
        $storeId = 0;
        if ($this->orderType == OrderTypes::PAID) {
            $storeId = $this->order['store_id'];
        } elseif ($this->orderType == OrderTypes::RUNNING) {
            $storeId = $this->reservation['store_id'];
        } elseif ($this->orderType == OrderTypes::SUB) {
            $storeId = $this->subOrder['store_id'];
        } elseif ($this->orderType == OrderTypes::SAVE) {
            $storeId = $this->saveOrder['store_id'];
        }
        $store = Store::with('storeSetting', 'takeawaySetting')->where('id', $storeId)->first();

        if (empty($store)) {
            throw new StoreEmptyException();
        }
        companionLogger('--Eatcard companion store details : ', $store);
        $this->store = $store;
    }

    protected function setDeviceData()
    {
        $deviceId = 0;
        // is device id is specified then need to replace it.
        if (! empty($this->additionalSettings['current_device_id']) && (int) $this->additionalSettings['current_device_id'] > 0) {
            $deviceId = (int) $this->additionalSettings['current_device_id'];
        } elseif ($this->orderType == OrderTypes::PAID) {
            $deviceId = $this->order['kiosk_id'];
//        } elseif ($this->order == OrderTypes::RUNNING) {
            // for reservation order device id must set on additional setting wia protocol
        } elseif ($this->orderType == OrderTypes::SUB) {
            $deviceId = $this->order['kiosk_id'];
        } elseif ($this->orderType == OrderTypes::SAVE) {
            $deviceId = $this->order['device_id'];
        }
        $kiosk = Cache::tags([
                                FLUSH_ALL,
                                FLUSH_POS,
                                FLUSH_STORE_BY_ID.$this->store->id,
                                KIOSK_DEVICES,
                                STORE_POS_SETTING,
                            ])
                                ->remember('{eat-card}-kiosk-device-with-settings'.$deviceId, caching_time, function () use ($deviceId) {
                                    return KioskDevice::with('settings')->where('id', $deviceId)->first();
                                });
        companionLogger('--Eatcard companion Kiosk Device details : ', $kiosk);
        $this->kiosk = $kiosk;
    }
}
