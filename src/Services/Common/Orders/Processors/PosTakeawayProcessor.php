<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Throwable;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

class PosTakeawayProcessor extends BaseProcessor
{
    protected string $createdFrom = 'pos';

    protected string $orderStatus = 'preparing';

    protected string $createdBy = '';

    protected string $savedOrderId = '';

    protected array $localRules = [];

    public function __construct()
    {
        parent::__construct();
        $this->setCreatedBy();
        $this->setSavedOrderId();
        $this->preparePayloadStore();
    }

    public function setCreatedBy(): void
    {
        if (! empty($this->payload) && isset($this->payload['pos_user_id'])) {
            $this->createdBy = $this->payload['pos_user_id'];
        }
    }

    public function setSavedOrderId(): void
    {
        if (! empty($this->inputs) && isset($this->inputs['saved_order_id'])) {
            $this->savedOrderId = $this->inputs['saved_order_id'];
        }
    }

    public function preparePayloadStore(): void
    {
        if (! empty($this->inputs) && isset($this->inputs['store_id'])) {
            $this->setStore($this->inputs['store_id']);
        }
    }

    /**
     * @return array
     */
    public function preparePayload(): array
    {
        $defaultPayload = parent::preparePayload();

        $overrideValues = ['item'  => 2, 'item2' => 2];

        $removeField = ['asd', 'zyx'];

        $final = [
            'order' => null,
            'order_items' => null,
        ];

        return array_merge($defaultPayload, ['item'  => 2, 'item2' => 2]);
    }

    /**
     * @throws Throwable
     */
    public function dispatch(): array
    {
        $this->validate($this->localRules);
        // TODO : Create logic goes here...
        $payloadFinal = $this->preparePayload();
        $order = $this->createOrder($payloadFinal['order']);
        $orderItems = $this->createOrderItems($order->id, $payloadFinal['order_items']);

        return $order;
    }

    /**
     * @return array
     */
    public function prepareValidationsRules(): array
    {
        $defaultValidationRules = parent::prepareValidationsRules();
        $overrideValidationRules = [

        ];

        $removeValidationRules = [];

        $this->localRules = array_merge($defaultValidationRules, $overrideValidationRules);
    }
}
