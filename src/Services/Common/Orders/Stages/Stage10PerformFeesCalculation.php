<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

/**
 * @description Stag 10
 */
trait Stage10PerformFeesCalculation
{
    protected function setAdditionalFees()
    {
        if ($this->settings['additional_fee']['status'] == true) {
            $this->orderData['additional_fee'] = $this->settings['additional_fee']['value'];
        }
    }

    protected function setDeliveryFees()
    {
        if ($this->settings['delivery_fee']['status'] == true) {
            $this->orderData['delivery_fee'] = $this->settings['delivery_fee']['value'];
        }
    }
}
