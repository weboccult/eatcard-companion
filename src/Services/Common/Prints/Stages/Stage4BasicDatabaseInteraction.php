<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Exceptions\KDSUserNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\NoKitchenPrintForUntilException;
use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\OrderNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\ReservationOrderItemEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\KdsUser;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\OrderReceipt;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
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

    protected function setReservationOrderItemData()
    {
        if ($this->orderType != OrderTypes::RUNNING) {
            return;
        }

        if ($this->orderType == OrderTypes::RUNNING && ! in_array($this->printType, [PrintTypes::DEFAULT,
                                                                                          PrintTypes::KITCHEN_LABEL,
                                                                                          PrintTypes::KITCHEN, PrintTypes::LABEL, ])) {
            return;
        }

        if (empty($this->reservationOrderItemId)) {
            throw new OrderIdEmptyException();
        }

        $reservationOrderItems = ReservationOrderItem::with(['table'])->where('id', $this->reservationOrderItemId)->first();

        if (empty($reservationOrderItems)) {
            throw new ReservationOrderItemEmptyException();
        }

        $this->reservationOrderItems = $reservationOrderItems;
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

        //fetch only order item if set in payload for kitchen print
        $orderItemId = $this->orderItemId;

        $order = Order::with([
            'orderItems' => function ($q1) use ($orderItemId) {
                $q1->when($orderItemId > 0, function ($q) use ($orderItemId) {
                    $q->where('id', $orderItemId);
                });
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
                'orderItems' => function ($q1) use ($orderItemId) {
                    $q1->when($orderItemId > 0, function ($q) use ($orderItemId) {
                        $q->where('id', $orderItemId);
                    });
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

        //if reservation order item data is set then take this reservation
        if (! empty($this->reservationOrderItems) && ! empty($this->reservationOrderItems->reservation_id)) {
            $this->reservationId = $this->reservationOrderItems->reservation_id;
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

        if ($this->orderType == OrderTypes::RUNNING && $this->printType != PrintTypes::PROFORMA && isset($reservation->is_until) && $reservation->is_until == 1) {
            throw new NoKitchenPrintForUntilException();
        }
        companionLogger('--Eatcard companion reservation details : ', $reservation);
        $this->reservation = $reservation;
    }

    protected function setSaveOrderData()
    {
        if ($this->orderType != OrderTypes::SAVE) {
            return;
        }

        if (empty($this->saveOrderId)) {
            throw new OrderIdEmptyException();
        }

        $saveOrder = OrderReceipt::query()->where('id', $this->saveOrderId)->first();

        if (empty($saveOrder)) {
            throw new OrderIdEmptyException();
        }

        $this->saveOrder = $saveOrder;
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
            $deviceId = $this->saveOrder['device_id'];
        }
        $kiosk = /*Cache::tags([
                                FLUSH_ALL,
                                FLUSH_POS,
                                FLUSH_STORE_BY_ID.$this->store->id,
                                KIOSK_DEVICES,
                                STORE_POS_SETTING,
                            ])
                                ->remember('{eat-card}-kiosk-device-with-settings'.$deviceId, CACHING_TIME, function () use ($deviceId) {
                                    return*/ KioskDevice::with('settings')->where('id', $deviceId)->first();
        /* });*/
        companionLogger('--Eatcard companion Kiosk Device details : ', $kiosk);
//        dd($kiosk);
        $this->kiosk = $kiosk;
    }

    protected function setKDSUserData()
    {
        if (empty($this->kdsUserId)) {
            return;
        }

        $kdsUser = KdsUser::query()->where('id', $this->kdsUserId)->first();

        if (empty($kdsUser)) {
            throw new KDSUserNotFoundException();
        }

        $this->kdsUser = $kdsUser;
    }
}
