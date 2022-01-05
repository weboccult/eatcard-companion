<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @description Stag 4
 * @mixin BaseProcessor
 */
trait Stage4EnableSettings
{
    protected function enableAdditionalFees()
    {
        if ($this->system == SystemTypes::POS) {
            if ($this->payload['method'] != 'cash' && isset($this->store->storeSetting) && $this->store->storeSetting->is_pin == 1 && ! empty($store->storeSetting->additional_fee)) {
                $this->settings['additional_fee'] = [
                    'status' => true,
                    'value'  => $store->storeSetting->additional_fee,
                ];
            }
        }
    }

    protected function enableDeliveryFees()
    {
    }

    protected function enableStatiegeDeposite()
    {
    }

    protected function enableNewLetterSubscription()
    {
    }

    protected function disableAdditionalFees()
    {
        $this->settings['additional_fee'] = [
            'status' => false,
            'value'  => null,
        ];
    }

    protected function disableDeliveryFees()
    {
    }

    protected function disableStatiegeDeposite()
    {
    }

    protected function disableNewLetterSubscription()
    {
    }
}
