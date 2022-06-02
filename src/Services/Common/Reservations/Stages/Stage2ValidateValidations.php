<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Carbon\Carbon;
use Throwable;
use Weboccult\EatcardCompanion\Exceptions\AYCEDataEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DineInPriceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationDateEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationTypeInvalidException;
use Weboccult\EatcardCompanion\Exceptions\SlotEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\StoreWeekDay;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 2
 */
trait Stage2ValidateValidations
{
    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateCommonRules()
    {
        try {
            foreach ($this->getCommonRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (StoreEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store not found']);
        }
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateSystemSpecificRules()
    {
        try {
            foreach ($this->getSystemSpecificRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (KioskDeviceEmptyException $e) {
            $this->setDumpDieValue(['error' => 'device not found']);
        } catch (StoreReservationEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store reservation not found']);
        } catch (ReservationTypeInvalidException $e) {
            $this->setDumpDieValue(['error' => 'reservation type not found or invalid']);
        } catch (ReservationDateEmptyException $e) {
            $this->setDumpDieValue(['error' => 'reservation date not found or invalid']);
        } catch (DineInPriceEmptyException $e) {
            $this->setDumpDieValue(['error' => 'dine-in price id not found or invalid']);
        } catch (AYCEDataEmptyException $e) {
            $this->setDumpDieValue(['error' => 'reservation AYCE data not found or invalid']);
        } catch (SlotEmptyException $e) {
            companionLogger('slot not found or invalid 1 : ', $this->slot);
            $this->setDumpDieValue(['error' => 'slot not found or invalid']);
        }
    }

    protected function validateExtraRules()
    {
    }

    protected function validateSlot()
    {
        if ($this->slotType == 'StoreSlot') {
            $storeWeekdaysId = $this->slot->store_weekdays_id ?? null;
            if (! empty($storeWeekdaysId)) {
                $storeWeekday = StoreWeekDay::find($storeWeekdaysId);
                if (! empty($storeWeekday) && ! empty($store_weekday->is_active)) {
                    companionLogger('store_weekday is not active  : ', $this->slot);
                    $this->setDumpDieValue(['error' => 'Selected reservation may not available at the moment. Contact support for more detail.']);
                }
            }
        }

        if (empty($this->slot->from_time)) {
            companionLogger('slot not found or invalid 2 : ', $this->slot);
            $this->setDumpDieValue(['error' => 'Selected reservation may not available at the moment. Contact support for more detail.']);
        }
    }

    protected function validateTime()
    {
        $current24Time = Carbon::now()->format('H:i');

        if ($this->reservationDate < Carbon::now()->format('Y-m-d') || ($this->reservationDate == Carbon::now()->format('Y-m-d') && strtotime($this->slot->from_time) <= strtotime($current24Time))) {
            companionLogger(' reservation date or time was passed : ', $this->reservationDate, $this->slot);
            $this->setDumpDieValue(['error' => 'Selected reservation may not available at the moment. Contact support for more detail.']);
        }

        // If current day is off.
        if ($this->reservationDate == Carbon::now()->format('Y-m-d') && $this->store->reservation_off_chkbx == 1) {
            companionLogger(' reservation day off from admin dashboard');
            $this->setDumpDieValue(['error' => 'Selected reservation may not available at the moment. Contact support for more detail.']);
        }
    }

    protected function validateSlotLimits()
    {
        $reservationPerson = $this->payload['person'] ?? 0;
        if ($this->slot->max_entries != 'Unlimited' && $reservationPerson > $this->slot->max_entries) {
            companionLogger('reservation person > to slot limit.');
            $this->setDumpDieValue(['error' => 'Selected reservation may not available at the moment. Contact support for more detail.']);
        }

        // here we not check with current reservation capacity of that slot because we already check while giving
        // slots and when assign table in cron so no need to check here
    }
}
