<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayDateNotAvailableException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeNotValidException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\TakeawaySettingNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\WorldLineSecretsNotFoundException;
use Weboccult\EatcardCompanion\Models\ZipCode;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\checkRadiusDistance;
use function Weboccult\EatcardCompanion\Helpers\checkZipCodeRange;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\getDistance;

/**
 * @description Stag 1
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage1PrepareValidationRules
{
    protected function overridableCommonRule()
    {
        // Add condition here... If you want to exclude store_id validation
        $this->addRuleToCommonRules(StoreEmptyException::class, (! isset($this->payload['store_id']) || empty($this->payload['store_id']) || empty($this->store)));

        if (! empty($this->device) && $this->device->payment_type == 'worldline') {
            $this->addRuleToCommonRules(WorldLineSecretsNotFoundException::class, ! file_exists(public_path('worldline/eatcard.nl.pem')));
        }
    }

    protected function overridableSystemSpecificRules()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSK])) {
            $this->addRuleToSystemSpecificRules(KioskDeviceEmptyException::class, empty($this->device));
        }
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']) && empty($this->storeReservation)) {
                $this->addRuleToSystemSpecificRules(StoreReservationEmptyException::class, empty($this->device));
            }
        }
        if ($this->system == SystemTypes::TAKEAWAY) {
            $this->addRuleToSystemSpecificRules(TakeawayOrderTypeEmptyException::class, (! isset($this->payload['order_type']) || empty($this->payload['order_type'])));
            $this->addRuleToSystemSpecificRules(TakeawayOrderTypeMisMatchedException::class, (! empty($this->payload['order_type']) && ! in_array($this->payload['order_type'], ['pickup', 'delivery'])));
            $this->addRuleToSystemSpecificRules(TakeawayPaymentMethodMisMatchedException::class, (! empty($this->payload['type']) && ! in_array($this->payload['type'], ['mollie', 'multisafepay'])));
            $this->addRuleToSystemSpecificRules(TakeawaySettingNotFoundException::class, empty($this->takeawaySetting));
            $this->addRuleToSystemSpecificRules(TakeawayPaymentMethodNotFoundException::class, (! isset($this->payload['method']) || empty($this->payload['method'])));
            if ((! isset($this->payload['order_date']) || empty($this->payload['order_date'])) || ! isset($this->payload['order_time']) || empty($this->payload['order_time'])) {
                $this->addRuleToSystemSpecificRules(TakeawayOrderDateTimeEmptyException::class, true);
                $order_date_time = $this->payload['order_date'].' '.$this->payload['order_time'].':00';
                $this->addRuleToSystemSpecificRules(TakeawayOrderDateTimeNotValidException::class, strtotime($order_date_time) < strtotime('-1 mins'));
            }

            $somePreOrderItemExist = collect($this->cart)->some('is_pre_order', '1');
            if ($this->takeawaySetting->is_single_day == 1 && ! $somePreOrderItemExist && $this->payload['order_date'] > Carbon::now()->format('Y-m-d')) {
                $this->addRuleToSystemSpecificRules(TakeawayDateNotAvailableException::class, true);
            }
        }
    }

    protected function setTakeawayPickupValidation()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            $gift_card_amount = $this->payload['gift_card_amount'] ?? 0;
            if ($this->payload['order_type'] == 'pickup' && ((! $gift_card_amount && $this->takeawaySetting->pickup_min_amount > $this->payload['total']) || ($gift_card_amount && $this->takeawaySetting->pickup_min_amount > $this->payload['old_total']))) {
                Session::flash('error', __('eatcard-companion::takeaway.order_must_greater', ['min_amount' => $this->takeawaySetting['pickup_min_amount']]));
                $this->setDumpDieValue(['error' => 'error']);
            }
        }
    }

    protected function setTakeawayDeliveryValidation()
    {
        if ($this->system == SystemTypes::TAKEAWAY) {
            $gift_card_amount = $this->payload['gift_card_amount'] ?? 0;
            if ($this->payload['order_type'] == 'delivery') {
                if ($this->takeawaySetting->zip_code_setting) {
                    $zip_codes = ZipCode::query()
                        ->where('store_id', $this->takeawaySetting->store_id)
                        ->where('status', 1)
                        ->get();
                    $user_zipcode = $this->payload['delivery_postcode'] ?? '';
                    if (empty($user_zipcode)) {
                        $this->setDumpDieValue(['delivery_not_available' => 'error']);
                    } elseif (count($zip_codes) > 0) {
                        $check_available_or_not = false;
                        foreach ($zip_codes as $zip_code) {
                            $user_zipcode = str_replace(' ', '', strtolower($user_zipcode));
                            $zipcode = explode('-', $user_zipcode);
                            $from_zip_code = $zip_code->from_zip_code;
                            $to_zip_code = $zip_code->to_zip_code;
                            $user_zip_code = '';
                            for ($i = 0; $i < count($zipcode); $i++) {
                                $user_zip_code .= $zipcode[$i];
                            }
                            if (! $check_available_or_not) {
                                $check_available_or_not = checkZipCodeRange(substr($user_zip_code, 0, 4), $from_zip_code, $to_zip_code);
                                if ($check_available_or_not) {
                                    $distance = getDistance($this->store->address, $this->payload['delivery_address']);
                                    companionLogger('Distance data fetched if delivery zipcode is on : ', $distance);
                                    if ($distance === 'ZERO_RESULTS') {
                                        $this->setDumpDieValue(['delivery_not_available' => 'error']);
                                    }
                                    if ((! $gift_card_amount && $zip_code->is_delivery_min_amount > (float) $this->payload['total']) || ($gift_card_amount && $zip_code->is_delivery_min_amount > (float) $this->payload['old_total'])) {
                                        /*not in used*/
                                        /*Session::flash('error', __('messages.order_must_greater', ['min_amount' => $zip_code->is_delivery_min_amount]));*/
                                        $this->setDumpDieValue([
                                            'minimum_amount_zip_code' => $zip_code->is_delivery_min_amount,
                                            'zip_code' => $from_zip_code.'-'.$to_zip_code.' range',
                                        ]);
                                    }
                                }
                            }
                        }
                        if (! $check_available_or_not) {
                            foreach ($zip_codes as $zip_code) {
                                $user_zipcode = strtolower($user_zipcode);
                                $user_zipcode = str_replace(' ', '', $user_zipcode);
                                $zipcode = explode('-', $user_zipcode);
                                $from_zip_code = $zip_code->from_zip_code;
                                $to_zip_code = $zip_code->to_zip_code;
                                $user_zip_code = '';
                                for ($i = 0; $i < count($zipcode); $i++) {
                                    $user_zip_code .= $zipcode[$i];
                                }
                                if (! $check_available_or_not) {
                                    $check_available_or_not = checkZipCodeRange($user_zip_code, $from_zip_code, $to_zip_code);
                                    // if(!$check_available_or_not) {
                                    //     $check_available_or_not = checkZipCodeRange($user_zip_code, $from_zip_code, $to_zip_code);
                                    // }
                                    if ($check_available_or_not) {
                                        $distance = getDistance($this->store->address, $this->payload['delivery_address']);
                                        companionLogger('Distance data fetched if delivery zipcode is on : ', $distance);
                                        if ($distance === 'ZERO_RESULTS') {
                                            $this->setDumpDieValue(['delivery_not_available' => 'error']);
                                        }
                                        if ((! $gift_card_amount && $zip_code->is_delivery_min_amount > (float) $this->payload['total']) || ($gift_card_amount && $zip_code->is_delivery_min_amount > (float) $this->payload['old_total'])) {
                                            /*not in used*/
                                            /*Session::flash('error', __('messages.order_must_greater', ['min_amount' => $zip_code->is_delivery_min_amount]));*/
                                            $this->setDumpDieValue([
                                                'minimum_amount_zip_code' => $zip_code->is_delivery_min_amount,
                                                'zip_code' => $from_zip_code.'-'.$to_zip_code.' range',
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                        if (! $check_available_or_not) {
                            $this->setDumpDieValue([
                                'zip_code_not_available' => 'error',
                                'user_zipcode' => $user_zipcode,
                            ]);
                        }
                    } else {
                        $this->setDumpDieValue([
                            'zip_code_not_available' => 'error',
                            'user_zipcode' => $user_zipcode,
                        ]);
                    }
                } elseif ($this->takeawaySetting->delivery_radius_setting) {
                    $store_address = $this->store->address;
                    $radius_data = checkRadiusDistance($store_address, $this->payload['delivery_address'], $this->takeawaySetting, (float) $this->payload['total']);
                    if (isset($radius_data['delivery_not_available'])) {
                        $this->setDumpDieValue(['delivery_not_available' => 'error']);
                    } elseif (isset($radius_data['distance_error'])) {
                        $this->setDumpDieValue(['distance_error' => $radius_data['distance']]);
                    } elseif (isset($radius_data['error'])) {
                        $this->setDumpDieValue(['distance_error' => $radius_data['error']]);
                    } else {
                        if ((! $gift_card_amount && $radius_data['delivery_minimum_amount'] > (float) $this->payload['total']) || ($gift_card_amount && $radius_data['delivery_minimum_amount'] > (float) $this->payload['old_total'])) {
                            Session::flash('error', __('eatcard-companion::takeaway.order_must_greater', ['min_amount' => $radius_data['delivery_minimum_amount']]));
                            $this->setDumpDieValue([
                                'minimum_amount_radius' => $radius_data['delivery_minimum_amount'],
                                'distance' => $radius_data['distance'],
                            ]);
                        }
                    }
                } else {
                    $this->setDumpDieValue(['both_setting_off' => 'error']);
                }
            }
        }
    }
}
