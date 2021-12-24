<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders;

use Illuminate\Support\Facades\Cache;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Throwable;
use Exception;

abstract class BaseProcessor implements BaseProcessorContract
{
    protected string $createdFrom = 'companion';

    protected string $orderStatus = 'received';

    protected string $createdBy = '';

    protected string $savedOrderId = '';

    protected array $payload = [];

    protected array $cart = [];

    protected Store $store;

    protected KioskDevice $device;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (! $this->createdFrom == 'companion') {
            throw new Exception('You need to define value of created_from on order processor class : '.get_class($this));
        }
    }

    /**
     * @return array
     */
    public function getCart(): array
    {
        return $this->cart;
    }

    /**
     * @param array $cart
     */
    public function setCart(array $cart): void
    {
        $this->cart = $cart;
    }

    /**
     * @return Store
     */
    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * @param $storeId
     */
    public function setStore($storeId): void
    {
        $store = Cache::tags([
            FLUSH_ALL,
            FLUSH_POS,
            FLUSH_STORE_BY_ID.$storeId,
            STORE_CHANGE_BY_ID.$storeId,
            STORE_SETTING,
            TAKEAWAY_SETTING.$storeId,
        ])->remember('{eat-card}-store-with-settings-'.$storeId, CACHING_TIME, function () use ($storeId) {
            return Store::with('storeSetting')->where('id', $storeId)->first();
        });
        $this->store = $store;
    }

    /**
     * @param $deviceId
     */
    public function setDevice($deviceId): void
    {
        $device = Cache::tags([
            FLUSH_ALL,
            FLUSH_POS,
            FLUSH_STORE_BY_ID.$deviceId,
            KIOSK_DEVICES,
        ])
            ->remember('{eat-card}-kiosk-device-with-code-'.$this->store->id.$deviceId, CACHING_TIME, function () use ($deviceId) {
                return KioskDevice::where('pos_code', $deviceId)->where('store_id', $this->store->id)->first();
            });
        $this->device = $device;
    }

    /**
     * @return array
     */
    public function preparePayload(): array
    {
        // TODO
        return $this->getCart();
    }

    /**
     * @return bool[]
     */
    public function prepareValidationsRules(): array
    {
        return [
            'Store can\'t be empty' => empty($this->store),
        ];
    }

    /**
     * @throws Throwable
     */
    public function validate($rules): void
    {
        foreach ($rules as $ex => $condition) {
            throw_if($condition, new Exception($ex, 422));
        }
    }

    public function createOrder(array $payload): array
    {
        // return Order::create($payload)
    }

    public function createOrderItems(string $orderId, array $items): array
    {
        // return OrderItem::create($payload)
    }
}
