<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 4
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage4EnableSettings
{
    protected function enableExtraSettings()
    {
        $this->settings['bop_kiosk'] = [
            'status' => false,
        ];
        if ($this->system == SystemTypes::KIOSK) {
            $this->settings['bop_kiosk']['status'] = (isset($this->payload['bop']) && $this->payload['bop'] == 'wot@kiosk');
            if ($this->settings['bop_kiosk']['status']) {
                companionLogger('-----Kiosk bop on for Kiosk device : ', ($this->orderData['kiosk_id'] ?? 0));
            }
        }
    }

    protected function enableAdditionalFees()
    {
        $isAdditionalFeeApply = false;
        $paymentMethod = $this->payload['method'] ?? '';

        if (! in_array($paymentMethod, ['cash', 'paylater']) || ! $this->settings['bop_kiosk']['status']) {
            $isAdditionalFeeApply = true;
        }

        if ($isAdditionalFeeApply && isset($this->store->storeSetting) && $this->store->storeSetting->is_online_payment == 1 && $this->store->storeSetting->additional_fee) {
            $this->settings['additional_fee'] = [
                'status' => true,
                'value'  => $this->store->storeSetting->additional_fee ?? 0,
                // 'value'  => $this->payload['additional_fee'] ?? 0,
                // here we're not using fee from frontend side payload
            ];
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
        $plasticBagFee = (float) ($this->payload['plastic_bag_fee'] ?? 0);
        $isPlasticBagFeeApply = false;

        //return if no fee come from input
        if (empty($plasticBagFee)) {
            return;
        }

        //we always get price from database
        $plasticBagFee = $this->store->storeSetting->plastic_bag_fee ?? 0;

        if ($this->system == SystemTypes::TAKEAWAY) {
            if (isset($this->store->storeSetting) && $this->store->storeSetting->is_bag_takeaway == 1 && ! empty($plasticBagFee)) {
                $isPlasticBagFeeApply = true;
            }
        }

        if ($this->system == SystemTypes::KIOSK) {
            if (isset($this->store->storeSetting) && $this->store->storeSetting->is_bag_kiosk == 1 && ! empty($plasticBagFee) &&
                $this->payload['dine_in_type'] != 'dine_in') {
                $isPlasticBagFeeApply = true;
            }
        }

        if ($this->system == SystemTypes::DINE_IN) {
            if (isset($this->store->storeSetting) && $this->store->storeSetting->is_bag_dinein == 1 && ! empty($plasticBagFee) &&
                $this->payload['dine_in_type'] != 'dine_in') {
                $isPlasticBagFeeApply = true;
            }
        }

        if ($isPlasticBagFeeApply) {
            $this->settings['plastic_bag_fee'] = [
                'status' => true,
                'value'  => $plasticBagFee,
            ];
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

    protected function enableNotification()
    {
        $isNotification = $this->store->is_notification ?? 0;
        $notificationSettings = $this->store->notificationSetting ?? [];
        $isTakeawayNotification = $this->store->notificationSetting->is_takeaway_notification ?? 0;
        $isDineInNotification = $this->store->notificationSetting->is_dine_in_notification ?? 0;

        $this->settings['notification'] = [
            'status' => false,
        ];

        //if setting is not exists then need to send all notification
        if (empty($notificationSettings)) {
            $this->settings['notification'] = [
                'status' => true,
            ];

            return;
        }

        if ($this->system == SystemTypes::TAKEAWAY) {
            if (! empty($isNotification) && ! empty($isTakeawayNotification)) {
                $this->settings['notification'] = [
                    'status' => true,
                ];
            }
        }

        if ($this->system == SystemTypes::DINE_IN) {
            if (! empty($isNotification) && ! empty($isDineInNotification)) {
                $this->settings['notification'] = [
                    'status' => true,
                ];
            }
        }
    }
}
