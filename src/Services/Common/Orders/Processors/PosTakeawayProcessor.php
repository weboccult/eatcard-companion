<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Exception;
use Throwable;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

class PosTakeawayProcessor extends BaseProcessor
{
    protected string $createdFrom = 'pos';

    protected string $orderStatus = 'preparing';

    public function __construct()
    {
        parent::__construct();
    }

    public function setCreatedByField(): void
    {
        if (! empty($this->payload) && isset($this->payload['pos_user_id'])) {
            $this->createdBy = $this->payload['pos_user_id'];
        }
    }

    public function setSavedOrderIdField(): void
    {
        if (! empty($this->payload) && isset($this->payload['saved_order_id'])) {
            $this->savedOrderId = $this->payload['saved_order_id'];
        }
    }

    /**
     * @throws Exception
     */
    public function setStoreModel(): void
    {
        if (! empty($this->payload) && isset($this->payload['store_id'])) {
            $this->setStore($this->payload['store_id']);
        }
    }

    public function setDeviceModel(): void
    {
        if (! empty($this->payload) && isset($this->payload['device_id'])) {
            $this->setDevice($this->payload['device_id']);
        }
    }

    /**
     * @return array
     */
    public function preparePayload(): array
    {
        $defaultPayload = parent::preparePayload();

        $overrideValues = [
            'order' => [],
            'order_items' => [
                'item2' => 'XXX',
                'item3' => 1,
            ],
        ];

        return array_replace_recursive($defaultPayload, $overrideValues);
    }

    /**
     * @return array
     */
    public function prepareValidationsRules(): array
    {
        $defaultValidationRules = parent::prepareValidationsRules();
        $overrideValidationRules = [
            KioskDeviceEmptyException::class => empty($this->device),
        ];

        $finalRules = array_merge($defaultValidationRules, $overrideValidationRules);

        $removeValidationRules = [
            // 'Store can\'t be empty',
        ];

        return collect($finalRules)->reject(fn ($value, $key) => in_array($key, $removeValidationRules))->toArray();
    }

    /**
     * @throws Throwable
     */
    public function dispatch(): array
    {
        $this->setCreatedByField();
        $this->setSavedOrderIdField();
        $this->setStoreModel();
        $this->setDeviceModel();

        $localRules = $this->prepareValidationsRules();
        $this->validate($localRules);

        // TODO : Create logic goes here...
        $payloadFinal = $this->preparePayload();

        dd($this);
        $order = $this->createOrder($payloadFinal['order']);
        $orderItems = $this->createOrderItems($order->id, $payloadFinal['order_items']);

        return $order;
    }
}
