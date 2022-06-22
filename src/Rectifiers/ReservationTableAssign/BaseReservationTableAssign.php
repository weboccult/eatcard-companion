<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign;

use Weboccult\EatcardCompanion\Exceptions\PersonEmptyException;
use Weboccult\EatcardCompanion\Exceptions\SlotEmptyException;
use Weboccult\EatcardCompanion\Models\CancelReservation;
use Weboccult\EatcardCompanion\Models\ReservationJob;
use Weboccult\EatcardCompanion\Models\ReservationTable;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Exceptions\MealEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DateEmptyException;
use Weboccult\EatcardCompanion\Exceptions\MealIdEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationOffException;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\StoreSlot;
use Weboccult\EatcardCompanion\Models\StoreSlotModified;
use Weboccult\EatcardCompanion\Models\StoreWeekDay;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\EatcardReservationSlots;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\KioskTickets\KioskTicketsTableAssign;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\Traits\Staggable;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\getLatestGroupId;

abstract class BaseReservationTableAssign
{
    use MagicAccessors;
    use AttributeHelpers;
    use Staggable;

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var StoreReservation|null|object */
    protected ?StoreReservation $currentReservation;

    /** @var Meal|null|object */
    protected ?meal $meal;

    /** @var |null|object */
    protected $slot;

    /** @var */
    public $storeId;

    public $reservationId;

    public $mealId;

    public $reservationDate;

    public $payload;

    public $nextForDay;

    protected array $commonRules = [];

    public $offDayAndDate;

    public $pickUpSlot;

    public $allReservation;

    public $tables;

    public $modelName;

    public $returnResponseData;

    public $assignedTables;

    public $assignedTablesPayload;

    public string $slotType = '';

    public $currentAssignTables;

    public int $reservationCheckAttempt = 0;

    protected ?array $dumpDieValue = null;

    /**
     * @param mixed $storeId
     *
     * @return BaseReservationTableAssign
     */
    public function setStoreId($storeId): self
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * @param mixed $storeId
     *
     * @return BaseReservationTableAssign
     */
    public function setReservationId($reservationId): self
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    /**
     * @param mixed $mealId
     *
     * @return BaseReservationTableAssign
     */
    public function setMealId($mealId): self
    {
        $this->mealId = $mealId;

        return $this;
    }

    /**
     * @param mixed $reservationDate
     *
     * @return BaseReservationTableAssign
     */
    public function setDate($reservationDate): self
    {
        $this->reservationDate = $reservationDate;

        return $this;
    }

    /**
     * @param mixed $payload
     *
     * @return BaseReservationTableAssign
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function dispatch()
    {
        return $this->stageIt([
            fn () => $this->Stage0SetDefaultData(),
            fn () => $this->Stage1PrepareValidationRules(),
            fn () => $this->Stage2ValidateValidationRules(),
            fn () => $this->Stage3PrepareBasicData(),
            fn () => $this->Stage4FindAndAssignedTables(),
            fn () => $this->Stage5ExtraOperations(),
            fn () => $this->Stage6UpdateReservationDetails(),
        ], true);
    }

    protected function Stage0SetDefaultData()
    {
        $this->store = Store::query()->find($this->storeId);

        $this->meal = Meal::query()->find($this->mealId);

        $this->currentReservation = StoreReservation::with('tables2')->where('id', $this->reservationId)->first();

        $this->currentAssignTables = $this->currentReservation->tables2->pluck('id')->toArray() ?? [];

        $slotType = $this->payload['data_model'] ?? '';

        $slotId = $this->payload['slot_id'] ?? '';
        if ($slotType == 'StoreSlot') {
            $this->slot = StoreSlot::where('id', $slotId)->first();
            if (isset($this->slot->store_weekdays_id) && $this->slot->store_weekdays_id != null) {
                $store_weekday = StoreWeekDay::find($this->slot->store_weekdays_id);
                if ($store_weekday && $store_weekday->is_active != 1) {
                    companionLogger('Fail to assign 1. Store weekday off : ', ($this->currentReservation->id ?? ''));
                    $this->FailReservationTableAssign();
                    $this->setDumpDieValue(['error' => 'Store weekday off']);
                }
            }
        } else {
            $this->slot = StoreSlotModified::where('id', $slotId)->first();
        }
    }

    protected function Stage1PrepareValidationRules()
    {
        $this->addRuleToCommonRules(StoreIdEmptyException::class, empty($this->storeId ?? 0));
        $this->addRuleToCommonRules(StoreEmptyException::class, empty($this->store ?? ''));
        $this->addRuleToCommonRules(MealIdEmptyException::class, empty($this->mealId ?? 0));
        $this->addRuleToCommonRules(MealEmptyException::class, empty($this->meal ?? ''));
        $this->addRuleToCommonRules(DateEmptyException::class, empty($this->reservationDate ?? ''));
        $this->addRuleToCommonRules(ReservationOffException::class, empty($this->currentReservation ?? ''));
        $this->addRuleToCommonRules(SlotEmptyException::class, empty($this->slot ?? ''));

        $this->addRuleToCommonRules(PersonEmptyException::class, empty($this->payload['person'] ?? ''));
    }

    protected function Stage2ValidateValidationRules()
    {
        companionLogger('----Table assign common rule  : ', $this->getCommonRules());
        foreach ($this->getCommonRules() as $ex => $condition) {
//            throw_if($condition, new $ex());
            if ($condition) {
                companionLogger('---Validation error', $ex);
                $this->FailReservationTableAssign();
                $this->setDumpDieValue(['error' => 'something wen wrong !']);
            }
        }
    }

    protected function Stage3PrepareBasicData()
    {
        $this->slotType = $this->payload['data_model'] ?? '';

        $this->assignedTablesPayload = [
          'person' => $this->payload['person'] ?? 0,
          'reservation_id' => $this->currentReservation->id ?? 0,
          'slot_id' => $this->slot->id ?? '',
          'slot_type' => $this->slotType,
        ];

        $this->reservationCheckAttempt = (int) ($this->payload['reservation_check_attempt'] ?? 0) + 1;
    }

    protected function Stage4FindAndAssignedTables()
    {
        $slotsWithTable = [];
        try {
            $slotsWithTable = EatcardReservationSlots::action(KioskTicketsTableAssign::class)
                                                   ->setStoreId($this->store->id)
                                                   ->setMealId($this->meal->id)
                                                   ->setDate($this->reservationDate)
                                                   ->setPayload($this->assignedTablesPayload)
                                                   ->fetch();

            companionLogger('Stage4GetPickUpTimeSlots response ', $slotsWithTable);
        } catch (\Exception $e) {
            companionLogger('Stage4GetPickUpTimeSlots', 'error : '.$e->getMessage(), 'file : '.$e->getFile(), 'line : '.$e->getLine(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'), );
            $slotsWithTable['error_messages'] = $e->getMessage();
        }

//        if (! empty($slotsWithTable['error_messages'])) {
//            companionLogger('Fail to assign 2. Store weekday off : ', ($this->currentReservation->id ?? ''), $slotsWithTable);
//            $this->FailReservationTableAssign();
//            $this->setDumpDieValue(['error' => 'Slot not available or full']);
//        }

        $this->assignedTables = $slotsWithTable['assigned_tables'][$this->slot->id] ?? [];

        if (empty($this->assignedTables)) {
            companionLogger(
                'Fail to assign 3. not available table found : ',
                ($this->currentReservation->id ?? ''),
                $slotsWithTable
            );
            $this->FailReservationTableAssign();
            $this->setDumpDieValue(['error' => 'Slot not available or full']);
        }
    }

    protected function Stage5ExtraOperations()
    {
    }

    protected function Stage6UpdateReservationDetails()
    {
        companionLogger('----new assigned table', $this->assignedTables);
        $updateReservationData = [];
        //check if reservation is group reservation then update group id
        if (! empty($this->assignedTables) && count($this->assignedTables) > 1) {
            $currentGroupId = $this->currentReservation->group_id ?? 0;
            //if reservation is already group reservation then no need to update it
            if (empty($currentGroupId)) {
                $groupId = getLatestGroupId($this->reservationDate, $this->store->id);
                $updateReservationData['group_id'] = $groupId + 1;
            }
        }

        if (! empty($this->assignedTables) && ! empty($this->currentAssignTables)) {

            //store data in json , user for remove or keep tables base on payment status paid or failed
            $ayceData = json_decode($this->currentReservation->all_you_eat_data, true);

            if (! empty($ayceData)) {
                $ayceData['oldAssignTables'] = $this->currentAssignTables;
                $ayceData['newAssignTables'] = $this->assignedTables;
                $ayceData = json_encode($ayceData);
                $updateReservationData['all_you_eat_data'] = $ayceData;

                $this->assignedTables = array_diff($this->assignedTables, $this->currentAssignTables);
                companionLogger('----new assigned table 2', $this->assignedTables);
            }
        }

        foreach ($this->assignedTables as $table_id) {
            ReservationTable::create([
                'reservation_id' => $this->currentReservation->id,
                'table_id'       => $table_id,
            ]);
            companionLogger('Table assign to reservation', [
                'reservation_id' => $this->currentReservation->id,
                'table_id'       => $table_id,
            ]);
        }

        $updateReservationData['res_status'] = 'success';

        companionLogger('----Update reservation data : ', $updateReservationData);
        StoreReservation::where('id', $this->currentReservation->id)->update($updateReservationData);

        $reservationJobId = $this->payload['reservation_job_id'] ?? 0;
        if (! empty($reservationJobId)) {
            ReservationJob::where('id', $reservationJobId)->delete();
        }

        $this->setDumpDieValue(['message' => 'Table assign done']);
    }

    private function FailReservationTableAssign()
    {
        $updateReservationData = [];
        if (! empty($this->currentAssignTables)) {
            $ayceData = json_decode($this->currentReservation->all_you_eat_data, true);
            if (! empty($ayceData)) {
                $ayceData['assignTableStatus'] = 'failed';
                $ayceData = json_encode($ayceData);
                $updateReservationData['all_you_eat_data'] = $ayceData;
            }
        }

        if (empty($this->currentAssignTables)) {
            $updateReservationData['res_status'] = 'failed';
        }

        if (! empty($updateReservationData)) {
            StoreReservation::where('id', $this->currentReservation->id)->update($updateReservationData);
        }

        $reservationJobId = $this->payload['reservation_job_id'] ?? 0;
        if (! empty($reservationJobId)) {
            ReservationJob::where('id', $reservationJobId)->delete();
        }

        if (empty($this->currentAssignTables)) {
            CancelReservation::create([
                'reservation_id'         => $this->currentReservation->id,
                'store_id'               => $this->store->id,
                'reservation_front_data' => $this->payload['reservation_front_data'] ?? $this->currentReservation,
                'reason'                 => 'Failed',
            ]);
        }
    }
}
