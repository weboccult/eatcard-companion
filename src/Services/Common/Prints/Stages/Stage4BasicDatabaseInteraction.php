<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Exceptions\KDSUserNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\NoKitchenPrintForUntilException;
use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\OrderNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\PaymentDetailsNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\ReservationOrderItemEmptyException;
use Weboccult\EatcardCompanion\Exceptions\SaveOrderEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Exceptions\SubOrderEmptyException;
use Weboccult\EatcardCompanion\Exceptions\SubOrderPrintSettingsDisableException;
use Weboccult\EatcardCompanion\Models\KdsUser;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\OrderReceipt;
use Weboccult\EatcardCompanion\Models\PaymentDetail;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 4
 * @mixin BaseGenerator
 */
trait Stage4BasicDatabaseInteraction
{
    /**
     * @return void
     * set sub order data
     * return if global sub order id not set.
     * return if sub order data not found.
     */
    protected function setSubOrderData()
    {
        if ($this->orderType != OrderTypes::SUB) {
            return;
        }

        if (empty($this->subOrderId)) {
            companionLogger('----Companion Print : No order id found for get order details');

            throw new SubOrderEmptyException();
        }

        $subOrder = SubOrder::query()->has('parentOrder')
                    ->with([
                            'subOrderItems' => function ($q1) {
                                $q1->with(['product' => function ($q2) {
                                    $q2->with('printers');
                                    $q2->withTrashed()->with(['category' => function ($q3) {
                                        $q3->withTrashed();
                                    }]);
                                }]);
                            },
                        ])
                    ->where('id', $this->subOrderId)->first();

        //validate as per order type
        if (empty($subOrder)) {
            throw new SubOrderEmptyException();
        }

        $this->subOrder = $subOrder->toArray();
        companionLogger('----Companion Print : sub order details', $this->subOrder);
    }

    /**
     * @return void
     * set reservation order item data for running order kitchen print
     * throw error if not reservation order item id found
     */
    protected function setReservationOrderItemData()
    {
        if ($this->orderType != OrderTypes::RUNNING) {
            return;
        }

        if (! in_array($this->printType, [PrintTypes::DEFAULT, PrintTypes::KITCHEN_LABEL, PrintTypes::KITCHEN, PrintTypes::LABEL])) {
            return;
        }

        if (empty($this->reservationOrderItemId)) {
            throw new OrderIdEmptyException();
        }

        $reservationOrderItems = ReservationOrderItem::with(['table'])->where('id', $this->reservationOrderItemId)->first();

        if (empty($reservationOrderItems)) {
            companionLogger('----Companion Print : No ReservationOrderItem found');
            throw new ReservationOrderItemEmptyException();
        }

        $this->reservationOrderItems = $reservationOrderItems;
        companionLogger('----Companion Print : setReservationOrderItemData details', $this->reservationOrderItems);
    }

    /**
     * @return void
     * set order data for save and sub order
     * return id global order id not set.
     * throw exception if order data not found
     */
    protected function setOrderData()
    {
        if (! in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            return;
        }

        if ($this->orderType == OrderTypes::SUB && ! empty($this->subOrder)) {
            $this->orderId = $this->subOrder['parent_order_id'] ?? 0;
        }

        if (empty($this->orderId)) {
            companionLogger('----Companion Print : No order id found for get order details');

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
        //if no details found in order then check on history table
        if (empty($order)) {
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
        if (empty($order) && in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            companionLogger('----Companion Print : No order Data found for get order details');
            throw new OrderNotFoundException();
        }
        $this->order = $order->toArray();
        companionLogger('----Companion Print : Order with details', $this->order);
    }

    /**
     * @return void
     * set reservation details
     * return if global reservation id is not set
     * throw exception if no reservation data found for running order
     * throw exception if until setting is on and call for kitchen print , because no need to print kitchen print for until
     */
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
            companionLogger('----Companion Print : No reservation found for get reservation');

            return;
        }

        $reservation = StoreReservation::with(['tables2'])->where('id', $this->reservationId)->first();

        //validate as per order type
        if (empty($reservation) && $this->orderType == OrderTypes::RUNNING) {
            throw new StoreReservationEmptyException();
        }

        //skip kitchen print for until
        if ($this->orderType == OrderTypes::RUNNING && $this->printType != PrintTypes::PROFORMA && isset($reservation->is_until) && $reservation->is_until == 1) {
            throw new NoKitchenPrintForUntilException();
        }
        companionLogger('----Companion Print reservation details : ', $reservation);
        $this->reservation = $reservation;
    }

    /**
     * @return void
     * set save order data
     * throw error if global save order id is not set
     */
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
            throw new SaveOrderEmptyException();
        }

        $this->saveOrder = $saveOrder;
        companionLogger('----Companion Print SaveOrder details : ', $saveOrder);
    }

    /**
     * @return void
     * set store data
     * based on order details set store id first
     * throw error if store data not found
     */
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

//        companionLogger('------Companion Print store details : ', $store);
        $this->store = $store;
    }

    /**
     * @return void
     * Set KDS User data for KDS Kitchen print
     * throw exception if data not found
     */
    protected function setPaymentDetails()
    {
        if (empty($this->paymentId)) {
            return;
        }

        $paymentDetail = PaymentDetail::query()->where('id', $this->paymentId)->first();

//        if (empty($paymentDetail)) {
//            throw new PaymentDetailsNotFoundException();
//        }

        companionLogger('------Companion Print payment details : ', $paymentDetail);
        $this->paymentDetail = $paymentDetail;
    }

    /**
     * @return void
     * set POS/Kiosk Device setting data
     * set POS/Kiosk device id, From Protocol OR Based on order details
     * For suborder, It's depend on device settings, so if suborder print is skipped then throw exception.
     * return if no  data found
     */
    protected function setDeviceData()
    {
        $deviceId = 0;
        // is device id is specified then need to replace it.
        if (! empty($this->additionalSettings['current_device_id']) && (int) $this->additionalSettings['current_device_id'] > 0) {
            $deviceId = (int) $this->additionalSettings['current_device_id'];
        } elseif (! empty($this->paymentDetail)) {
            $deviceId = $this->paymentDetail['kiosk_id'];
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
                                ->remember('{eat-card}-companion-kiosk-device-with-settings'.$deviceId, CACHING_TIME, function () use ($deviceId) {
                                    return*/ KioskDevice::with('settings')->where('id', $deviceId)->first();
        /* });*/

        //skip print if SubOrder print settings is disable
        if ($this->orderType == OrderTypes::SUB && ! empty($kiosk) && isset($kiosk->settings->is_print_split) && $kiosk->settings->is_print_split == 0) {
            throw new SubOrderPrintSettingsDisableException();
        }

        companionLogger('------Companion Print kiosk details : ', $kiosk);
        $this->kiosk = $kiosk;
    }

    /**
     * @return void
     * Set KDS User data for KDS Kitchen print
     * throw exception if data not found
     */
    protected function setKDSUserData()
    {
        if (empty($this->kdsUserId)) {
            return;
        }

        $kdsUser = KdsUser::query()->where('id', $this->kdsUserId)->first();

        if (empty($kdsUser)) {
            throw new KDSUserNotFoundException();
        }

        companionLogger('------Companion Print kiosk user : ', $kdsUser);
        $this->kdsUser = $kdsUser;
    }
}
