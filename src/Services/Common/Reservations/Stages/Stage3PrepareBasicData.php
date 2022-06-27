<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\DineinPrices;
use function Weboccult\EatcardCompanion\Helpers\calculateAllYouCanEatPerson;
use function Weboccult\EatcardCompanion\Helpers\generateRandomNumberV2;
use function Weboccult\EatcardCompanion\Helpers\generateReservationId;
use function Weboccult\EatcardCompanion\Helpers\getAycePrice;

/**
 * @description Stag 3
 */
trait Stage3PrepareBasicData
{
    protected function prepareBasicData()
    {
        $this->reservationData['res_date'] = Carbon::parse($this->payload['res_date'])->format('Y-m-d');
        $this->reservationData['reservation_type'] = $this->payload['reservation_type'] ?? '';
        $this->reservationData['user_id'] = $this->payload['user_id'] ?? null;
        $this->reservationData['status'] = 'approved';
        $this->reservationData['reservation_sent'] = 0;
        $this->reservationData['created_from'] = $this->createdFrom;
        $this->reservationData['reservation_id'] = generateReservationId();
        $this->reservationData['gastpin'] = generateRandomNumberV2();
        $this->reservationData['voornaam'] = $this->payload['voornaam'] ?? 'Guest';
        $this->reservationData['achternaam'] = $this->payload['achternaam'] ?? '';
        $this->reservationData['email'] = $this->payload['email'] ?? '';
        $this->reservationData['geboortedatum'] = $this->payload['geboortedatum'] ?? '';
        $this->reservationData['res_origin'] = $this->payload['res_origin'] ?? '';
        $this->reservationData['gastnaam'] = $this->payload['gastnaam'] ?? '';
        $this->reservationData['comments'] = $this->payload['comments'] ?? '';
        $this->reservationData['section_id'] = $this->payload['section_id'] ?? 0;
        $this->reservationData['method'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
        $this->reservationData['payment_method_type'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';

        if ($this->system == SystemTypes::KIOSKTICKETS) {
            $this->reservationData['slot_model'] = $this->slotType;
            $this->reservationData['from_time'] = Carbon::parse($this->slot->from_time)->format('H:i');
            $this->reservationData['res_time'] = Carbon::parse($this->slot->from_time)->format('H:i');

            $time_limit = ($this->meal->time_limit) ? (int) $this->meal->time_limit : 120;
            $this->reservationData['end_time'] = Carbon::parse($this->slot->from_time)->addMinutes($time_limit)->format('H:i');

            if (strtotime($this->slot->from_time) > strtotime($this->reservationData['end_time'])) {
                $this->reservationData['end_time'] = '23:59';
            }
        }

        if ($this->system == SystemTypes::POS) {
            $this->reservationData['status'] = 'approved';
            $this->reservationData['from_time'] = $this->reservationData['res_time'] = Carbon::parse($this->payload['from_time'])->format('H:i');
            $this->reservationData['end_time'] = Carbon::parse($this->payload['from_time'])->addMinutes($this->meal->time_limit)->format('H:i');

            if (strtotime($this->reservationData['end_time']) >= strtotime('24:00')) {
                $this->reservationData['end_time'] = '23:59';
            }
        }

        $this->isBOP = isset($this->payload['bop']) && $this->payload['bop'] == 'wot@tickets';
        $method = $this->payload['method'] ?? '';
        $manualPin = $this->payload['manual_pin'] ?? '';
        if ($this->isBOP) {
            $this->reservationData['method'] = 'cash';
            $this->reservationData['payment_method_type'] = '';
        } elseif (! empty($manualPin)) {
            $this->reservationData['method'] = 'manual_pin';
            $this->reservationData['payment_method_type'] = '';
        } elseif ($method == 'cash') {
            $this->reservationData['method'] = 'cash';
            $this->reservationData['payment_method_type'] = '';
        }
    }

    protected function prepareAllYouCanEatData()
    {
        $this->reservationData['all_you_eat_data'] = null;
        $this->reservationData['dinein_price_id'] = $dineInPriceId = $this->payload['dinein_price_id'] ?? 0;

        if ($this->reservationData['reservation_type'] != 'all_you_eat') {
            return;
        }

        $ayceData = $this->payload['ayceData'] ? json_decode($this->payload['ayceData'], true) : [];

        $dineInPrice = DineinPrices::withTrashed()->with([
                            'meal',
                            'dineInCategory',
                            'dynamicPrices',
                        ])->where('id', $dineInPriceId)->first()->toArray();

        if (isset($ayceData['dynm_kids']) && ! empty($ayceData['dynm_kids'])) {
            $ayceDynamicChildeList = collect($ayceData['dynm_kids']);
            $aycePriceClassIds = $ayceDynamicChildeList->pluck('id')->toArray();
            foreach ($dineInPrice['dynamic_prices'] as $dy_price_key => $dynamicPrices) {
                if (isset($ayceDynamicChildeList) && isset($aycePriceClassIds) && in_array($dynamicPrices['id'], $aycePriceClassIds)) {
                    $ayce_person = collect($ayceDynamicChildeList)
                        ->where('id', $dynamicPrices['id'])
                        ->first();
                    $dineInPrice['dynamic_prices'][$dy_price_key]['person'] = isset($ayce_person['person']) && ! empty($ayce_person['person']) ? (int) $ayce_person['person'] : 0;
                }
            }
        }
        if ($dineInPrice && $ayceData) {
            $ayceData['dinein_price'] = $dineInPrice;
        }

        //on-the-house data with default value
        $ayceData['house'] = false;
        $ayceData['adult'] = 0;
        $ayceData['kid2'] = 0;
        $ayceData['kid1'] = 0;

        $this->reservationData['all_you_eat_data'] = $ayceData;
    }

    public function preparePaymentData()
    {
        if (empty($this->reservationData['all_you_eat_data'])) {
            return;
        }

        $this->reservationData['person'] = calculateAllYouCanEatPerson($this->reservationData['all_you_eat_data']);

        $aycePrice = getAycePrice($this->reservationData['all_you_eat_data']);

        $this->reservationData['total_price'] = $aycePrice;
        $this->reservationData['original_total_price'] = $aycePrice;
        $this->reservationData['payment_type'] = 'full_payment';
        $this->reservationData['is_manually_cancelled'] = 0;
        $this->reservationData['payment_status'] = 'pending';
        $this->reservationData['local_payment_status'] = 'pending';
    }
}
