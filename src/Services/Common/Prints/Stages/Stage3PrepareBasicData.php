<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Exceptions\OrderIdEmptyException;

/**
 * @description Stag 3
 */
trait Stage3PrepareBasicData
{
    protected function prepareRefOrderId()
    {
        $globalOrderId = $this->payload['order_id'];
        if (strpos($globalOrderId, 'pos') !== false) {
            $this->additionalSettings['exclude_print_status'] = true;
            $order_id = explode('pos', $globalOrderId);
            $this->globalOrderId = $order_id[0];
        } else {
            $this->globalOrderId = $globalOrderId;
        }

        if (empty($this->globalOrderId)) {
            throw new OrderIdEmptyException();
        }
    }

    protected function prepareOrderData()
    {
        if ($this->orderType == OrderTypes::PAID) {
            $this->orderId = $this->globalOrderId;
        }
    }

    protected function prepareReservationData()
    {
        if ($this->orderType == OrderTypes::RUNNING) {
            $this->reservationId = $this->globalOrderId;
        }
    }

    protected function prepareSubOrderData()
    {
        if ($this->orderType == OrderTypes::SUB) {
            $this->subOrderId = $this->globalOrderId;
        }
    }

    protected function prepareSaveOrderData()
    {
        if ($this->orderType == OrderTypes::SAVE) {
            $this->saveOrderId = $this->globalOrderId;
        }
    }

    protected function prepareDeviceId()
    {
        if (! empty($this->payloadRequestDetails)) {
            $this->additionalSettings['current_device_id'] = (int) ($this->payloadRequestDetails['deviceId'] ?? 0);
        }
    }
}
