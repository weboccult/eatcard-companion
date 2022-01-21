<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @description Stag 4
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
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
                    // 'value'  => $this->payload['additional_fee'] ?? 0,
                    // here we're not using fee from frontend side payload
                ];
            }
        }
        if ($this->system == SystemTypes::TAKEAWAY) {
            if (isset($this->store->storeSetting) && $this->store->storeSetting->is_online_payment == 1 && $this->store->storeSetting->additional_fee) {
                $this->settings['additional_fee'] = [
                    'status' => true,
                    'value'  => $this->store->storeSetting->additional_fee ?? 0,
                    // 'value'  => $this->payload['additional_fee'] ?? 0,
                    // here we're not using fee from frontend side payload
                ];
            }
        }
    }

    protected function enableDeliveryFees()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->settings['delivery_fee'] = [
                'status' => true,
                'value'  => $this->payload['delivery_fee'] ?? 0,
                // Todo : this delivery fee should be calculated again at the backend
            ];
        }
    }

    protected function enablePlasticBagFees()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            if (isset($this->store->storeSetting) && $this->store->storeSetting->is_bag_takeaway == 1 && $this->store->storeSetting->plastic_bag_fee) {
                $this->settings['plastic_bag_fee'] = [
                    'status' => true,
                    'value'  => $this->store->storeSetting->plastic_bag_fee ?? 0,
                    // 'value'  => $this->payload['plastic_bag_fee'] ?? 0,
                    // here we're not using fee from frontend side payload
                ];
            }
        }
    }

    protected function enableStatiegeDeposite()
    {
    }

    protected function enableNewLetterSubscription()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->settings['news_letter'] = [
                'status' => isset($this->payload['is_subscribe']),
            ];
        }
    }
}
