<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationSlots;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\DiningArea;
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
use Weboccult\EatcardCompanion\Models\Table;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Rectifiers\ReservationSlots\Traits\MagicAccessors;
use function Weboccult\EatcardCompanion\Helpers\bestsum;
use function Weboccult\EatcardCompanion\Helpers\checkSlotAvailability;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\getAnotherMeeting;

abstract class BaseReservationSlots
{
    use MagicAccessors;
    use AttributeHelpers;

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var Meal|null|object */
    protected ?meal $meal;

    /** @var */
    public $storeId;

    public $mealId;

    public $date;

    public $payload;

    public $nextForDay;

    protected array $commonRules = [];

    public $offDayAndDate;

    public $pickUpSlot;

    public $allReservation;

    public $tables;

    public $modelName;

    public $returnResponseData;

    public $systemType = 'companion';

    public bool $isTableAssign = false;
    public array $assignedTables = [];

    public function __construct()
    {
    }

    /**
     * @param mixed $storeId
     *
     * @return BaseReservationSlots
     */
    public function setStoreId($storeId): self
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * @param mixed $mealId
     *
     * @return BaseReservationSlots
     */
    public function setMealId($mealId): self
    {
        $this->mealId = $mealId;

        return $this;
    }

    /**
     * @param mixed $date
     *
     * @return BaseReservationSlots
     */
    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param mixed $payload
     *
     * @return BaseReservationSlots
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
        try {
            $this->Stage0SetDefaultData();
            $this->Stage1PrepareValidationRules();
            $this->Stage2ValidateValidationRules();
            $this->Stage3SetStoreOffDates();
            $this->Stage4GetPickUpTimeSlots();
            $this->Stage5EnableDisablePickUpSlotByPastTimeOff();
            $this->Stage6EnableDisablePickUpSlotBySlotCapacity();
            $this->Stage7EnableDisablePickUpSlotByTableAndSmartFit();
            $this->Stage8prepareAndSendJsonResponse();

            return $this->returnResponseData;
        } catch (ReservationOffException $e) {
            $this->returnResponseData['disable_dates'] = $this->offDayAndDate;
            throw new \Exception($e->getMessage() ?? 'Something went wrong...!');
        } catch (\Exception $e) {
            companionLogger('----Companion slots Exception', $e->getMessage(), $e->getFile(), $e->getLine());
            throw new \Exception($e->getMessage() ?? 'Something went wrong...!');
        }
    }

    protected function Stage0SetDefaultData()
    {
        $this->store = Store::query()->find($this->storeId);
        $this->meal = Meal::query()->find($this->mealId);

        // cron = table assing
        if ($this->systemType == SystemTypes::CRON) {
            $this->isTableAssign = true;
        }
    }

    protected function Stage1PrepareValidationRules()
    {
        $this->addRuleToCommonRules(StoreEmptyException::class, empty($this->store ?? ''));
        $this->addRuleToCommonRules(MealEmptyException::class, empty($this->meal ?? ''));
        $this->addRuleToCommonRules(StoreIdEmptyException::class, empty($this->storeId ?? 0));
        $this->addRuleToCommonRules(MealIdEmptyException::class, empty($this->mealId ?? 0));
        $this->addRuleToCommonRules(DateEmptyException::class, empty($this->date ?? ''));
    }

    protected function Stage2ValidateValidationRules()
    {
        companionLogger('----Tickets common rule  : ', $this->getCommonRules());
        foreach ($this->getCommonRules() as $ex => $condition) {
            throw_if($condition, new $ex());
        }
    }

    protected function Stage3SetStoreOffDates()
    {
        $this->date = Carbon::parse($this->date)->format('Y-m-d');

        if ($this->isTableAssign) {
            $this->nextForDay = Carbon::parse($this->date)->format('Y-m-d');
        } else {
            $this->nextForDay = Carbon::parse($this->date)->addDays(3)->format('Y-m-d');
        }

        $isMealModified = ($this->meal->is_meal_res == 1) || $this->meal->is_week_meal_res == 1;

        $slotModifiedData = StoreSlotModified::query()
                        ->where('meal_id', $this->mealId)
                        ->when(! empty($this->meal->is_meal_res), function ($q) {
                            $q->where('is_day_meal', 1);
                        })
                        ->when(! $isMealModified, function ($q2) {
                            $q2->where('is_day_meal', 0);
                        })
                        ->where('store_id', $this->storeId)
//                        ->whereBetween('store_date', [$this->date, $this->nextForDay])
                        ->when($this->isTableAssign, function ($q) {
                            $q->where('store_date', $this->date);
                        })
                        ->when(! $this->isTableAssign, function ($q) {
                            $q->whereBetween('store_date', [Carbon::now()->format('Y-m-d'), $this->nextForDay]);
                        })
                        ->orderBy('store_date', 'desc')
                        ->get()
                        ->toArray();

//        companionLogger('--------date wise slot', $slotModifiedData);
//        companionLogger('--------meal is modified', $isMealModified);
        $storeWeekDays = [];
        if (! $isMealModified || $this->meal->is_week_meal_res == 1) {
//            companionLogger('--------day wise meal', $this->meal->is_week_meal_res);
            $storeWeekDays = StoreWeekDay::query()
                ->when(($this->meal->is_week_meal_res == 1), function ($q) {
                    $q->leftjoin('store_slots', 'store_slots.store_weekdays_id', '=', 'store_weekdays.id');
                    $q->where('store_slots.store_id', $this->storeId);
                    $q->where('store_slots.meal_id', $this->mealId);
                    $q->where('store_weekdays.is_week_day_meal', 1);
                })
                ->when(! ($this->meal->is_week_meal_res == 1), function ($q1) {
                    $q1->where('is_week_day_meal', 0);
                    $q1->whereNull('is_active');
                    $q1->where('store_id', $this->storeId);
                })
                ->selectRaw('DISTINCT store_weekdays.name, store_weekdays.is_active ,store_weekdays.is_week_day_meal')
                ->get()
                ->toArray();
//            companionLogger('--------slot day wise', $storeWeekDays);
        }

        $this->offDayAndDate = $this->getOffDates($slotModifiedData, $storeWeekDays);
//        companionLogger('--------offDayAndDate', $this->offDayAndDate);
    }

    protected function Stage4GetPickUpTimeSlots()
    {
//        companionLogger('--------Stage4GetPickUpTimeSlots', $this->offDayAndDate, $this->date, in_array($this->date, $this->offDayAndDate->toArray()));
        if (! empty($this->offDayAndDate) && in_array($this->date, $this->offDayAndDate->toArray())) {
            throw new ReservationOffException();
        }

        $this->pickUpSlot = [];
        $slotId = 0;
        if ($this->isTableAssign) {
            $slotId = $this->payload['slot_id'] ?? '';
        }

        if ($this->meal->is_meal_res) {
            $isSlotModifiedAvailable = 1;
        } else {
            $isSlotModifiedAvailable = StoreSlotModified::query()
                ->where('store_id', $this->storeId)
                ->when($this->isTableAssign && ! empty($slotId), function ($q) use ($slotId) {
                    $q->where('id', $slotId);
                })
                ->where('is_day_meal', 0)
                ->where('store_date', $this->date)
                ->where('is_available', 1)
                ->count();
        }

        $isDaySlotExist = StoreWeekDay::where('store_id', $this->storeId)
            ->where('name', Carbon::parse($this->date)->format('l'))
            ->when($this->isTableAssign && ! empty($slotId), function ($q) use ($slotId) {
                $q->where('id', $slotId);
            })
            ->when(! $this->meal->is_meal_res && $this->meal->is_week_meal_res, function ($q) {
                $q->where('is_week_day_meal', 1);
            })
            ->when(! (! $this->meal->is_meal_res && $this->meal->is_week_meal_res), function ($q) {
                $q->where('is_week_day_meal', 0);
            })
            ->first();

        if ($isSlotModifiedAvailable > 0) {
            $isSlotModifiedAvailable = StoreSlotModified::where('store_id', $this->storeId)
                ->when($this->isTableAssign && ! empty($slotId), function ($q) use ($slotId) {
                    $q->where('id', $slotId);
                })
                ->where('meal_id', $this->mealId)
                ->where('store_date', $this->date)
                ->where('is_available', 1)
                ->orderBy('from_time', 'ASC')
                ->get();

            companionLogger('-------Test log 1--------------------------');
            $this->pickUpSlot = $isSlotModifiedAvailable;
            $this->modelName = 'StoreSlotModified';
        } elseif ($isDaySlotExist) {
            $daySlot = StoreSlot::leftJoin('store_weekdays', 'store_weekdays.id', '=', 'store_slots.store_weekdays_id', function ($q) {
                $q->where('store_weekdays.is_active', 1)->where('store_weekdays.name', Carbon::parse($this->date)->format('l'))
                ->when(! $this->meal->is_meal_res && $this->meal->is_week_meal_res, function ($q1) {
                    $q1->where('store_weekdays.is_week_day_meal', 1);
                })
                ->when(! (! $this->meal->is_meal_res && $this->meal->is_week_meal_res), function ($q2) {
                    $q2->where('store_weekdays.is_week_day_meal', 0);
                });
            })->where('store_slots.store_id', $this->storeId)
            ->when($this->isTableAssign && ! empty($slotId), function ($q) use ($slotId) {
                $q->where('id', $slotId);
            })
            ->where('store_weekdays.id', $isDaySlotExist->id)
            ->select('store_slots.*')
            ->where('store_slots.meal_id', $this->mealId)
            ->where('store_slots.store_weekdays_id', '!=', null)
            ->orderBy('store_slots.from_time', 'ASC')
            ->get();
            companionLogger('-------Test log 2--------------------------');
            $this->pickUpSlot = $daySlot;
            $this->modelName = 'StoreSlot';
        } else {
            $defaultSlot = StoreSlot::where('store_id', $this->storeId)
                ->where('meal_id', $this->mealId)
                ->when($this->isTableAssign && ! empty($slotId), function ($q) use ($slotId) {
                    companionLogger('-------Test log 3.1--------------------------', $slotId);
                    $q->where('id', $slotId);
                })
                ->doesntHave('store_weekday')
                ->orderBy('from_time', 'ASC')
                ->get();
            companionLogger('-------Test log 3--------------------------', $slotId);
            $this->pickUpSlot = $defaultSlot;
            $this->modelName = 'StoreSlot';
        }
    }

    protected function Stage5EnableDisablePickUpSlotByPastTimeOff()
    {
        companionLogger('-----Selected Pickup slot : ', $this->pickUpSlot);
        if ($this->date == Carbon::now()->format('Y-m-d')) {
            $current24Time = Carbon::now()->format('G:i');
//            collect($this->pickUpSlot)->map(function ($pickTime, $index) use ($current24Time) {
            foreach ($this->pickUpSlot as $index => $pickTime) {
                $this->pickUpSlot[$index]->disable = false;

                // Checking if time has been past for today
                if (strtotime($pickTime->from_time) <= strtotime($current24Time) && $this->date >= Carbon::now()->format('Y-m-d')) {
                    companionLogger('1. Slot is disable | stage :- Stage5EnableDisablePickUpSlotByPastTimeOff', $pickTime->from_time);
                    $this->pickUpSlot[$index]->disable = true;
                    continue;
                }
                // check if Set time is equal to current time or bigger than SET TIME
                $bookingOffTime = strtotime($this->store->booking_off_time == '00:00' ? '24:00' : $this->store->booking_off_time);
                if ($this->store->is_booking_enable == 1 && strtotime($current24Time) >= $bookingOffTime && $this->date >= Carbon::now()->format('Y-m-d')) {
                    if (strtotime($pickTime->from_time) >= strtotime($current24Time)) {
                        companionLogger('2. Slot is disable | stage :- Stage5EnableDisablePickUpSlotByPastTimeOff', $pickTime->from_time);
                        $this->pickUpSlot[$index]->disable = true;
                        continue;
                    }
                }
            }
        }
    }

    protected function Stage6EnableDisablePickUpSlotBySlotCapacity()
    {
        $reservationId = $this->payload['reservation_id'] ?? null;
        $person = $this->payload['person'] ?? 2;

        $this->allReservation = StoreReservation::with('tables.table.diningArea', 'meal')
                    ->where('store_id', $this->storeId)
                    ->where('res_date', $this->date)
                    ->when(! empty($reservationId), function ($q) use ($reservationId) {
                        $q->where('id', '!=', $reservationId);
                    })->whereNotIn('status', ['declined', 'cancelled'])
                    ->where(function ($q1) {
                        $q1->whereIn('local_payment_status', ['paid', '', 'pending'])->orWhere('total_price', null);
                    })
                    ->where('is_seated', '!=', 2)
                    ->get();

//        collect($this->pickUpSlot)->map(function ($pickTime, $index) use ($person) {
        foreach ($this->pickUpSlot as $index => $pickTime) {
            if ($pickTime->disable == false) {
                if ($this->store->reservation_off_chkbx == 1 && $this->date == Carbon::now()->format('Y-m-d')) {
                    companionLogger('Reservation booking setting is off');
                    $this->pickUpSlot[$index]->disable = true;
                    continue;
                }
//                companionLogger('--------slots', $pickTime);
                if (! empty($pickTime->is_slot_disabled) && ($this->date == Carbon::now()->format('Y-m-d'))) {
                    companionLogger('Slot is off from admin dashboard');
                    $this->pickUpSlot[$index]->disable = true;
                    continue;
                }
                if ($pickTime->max_entries != 'Unlimited') {
                    if ($person > $pickTime->max_entries) {
                        companionLogger('3. Slot is disable | stage :- Stage6EnableDisablePickUpSlotBySlotCapacity', $pickTime->from_time);
                        $this->pickUpSlot[$index]->disable = true;
                        continue;
                    } else {
                        $endTime = checkSlotAvailability($pickTime->from_time, $index, $this->pickUpSlot);
                        $assignPersons = $this->allReservation->where('from_time', '>=', $pickTime->from_time)
                            ->where('from_time', '<', $endTime)
                            ->sum('person');
                        $remainPersons = (int) $pickTime->max_entries - (int) ($assignPersons ?? 0);
                        if ($person > $remainPersons) {
                            companionLogger('4. Slot is disable | stage :- Stage6EnableDisablePickUpSlotBySlotCapacity', $pickTime->from_time);
                            $this->pickUpSlot[$index]->disable = true;
                            continue;
                        }
                    }
                }
            }
        }
    }

    protected function Stage7EnableDisablePickUpSlotByTableAndSmartFit()
    {
        $person = $this->payload['person'] ?? 2;
        if ($this->store->is_table_mgt_enabled == 1 && $this->store->is_smart_res) {
            $this->tables = Table::leftJoin('dining_areas', 'dining_areas.id', '=', 'tables.dining_area_id')
                ->select('tables.*')
                ->where('dining_areas.store_id', $this->storeId)
                ->where('dining_areas.is_automatic', 1)
                ->where('dining_areas.status', 1)
                ->where('tables.status', 1)
                ->where('tables.online_status', 1)
                ->get();

//            $this->pickUpSlot->map(function ($pickTime, $index) use ($person) {
            foreach ($this->pickUpSlot as $index => $pickTime) {
                if ($pickTime->disable == false) {
                    $tableIds = $this->tables->pluck('id')->toArray();
                    $assignTables = [];

                    foreach ($this->allReservation as $reservation) {
                        $anotherMeeting = getAnotherMeeting($reservation, $this->meal, $pickTime);
                        if ($anotherMeeting) {
                            collect($reservation->tables)->each(function ($q) use (&$assignTables) {
                                $assignTables[] = $q->table_id;
                            });
                        }
                    }
//                    companionLogger('-------assignTables', $assignTables);

                    $availableTables = collect(array_diff($tableIds, $assignTables))->values()->toArray();

//                    companionLogger('-------availableTables', $availableTables);
                    if (! $availableTables) {
                        companionLogger('5. Slot is disable | stage :- Stage7EnableDisablePickUpSlotByTableAndSmartFit', $pickTime->from_time, $person);
                        $this->pickUpSlot[$index]->disable = true;
                        continue;
                    }

                    if ($this->store->is_smart_fit) {
                        foreach ($availableTables as $table) {
                            $singleTable = $this->tables->where('id', $table)
                                ->first(function ($value, $key) use ($person) {
                                    return $value->no_of_min_seats <= $person && ($value->no_of_seats) >= $person + 1;
                                });

                            if (! empty($singleTable)) {
                                companionLogger('Slot is enable-1 | stage :- Stage7EnableDisablePickUpSlotByTableAndSmartFit', $pickTime->from_time, $person);
                                $this->pickUpSlot[$index]->disable = false;
                                $this->assignedTables[$pickTime->id][] = $singleTable->id;
                                continue 2;
                            }
                        }

                        if (empty($this->store->allow_auto_group)) {
                            companionLogger('6. Slot is disable | stage :- Stage7EnableDisablePickUpSlotByTableAndSmartFit', $pickTime->from_time, $person);
                            $this->pickUpSlot[$index]->disable = true;
                            continue;
                        }

                        $this->pickUpSlot[$index]->disable = true;

                        /*get available tables section wise*/
                        $sections = DiningArea::with([
                            'tables' => function ($q1) use ($availableTables, $person) {
                                $q1->whereIn('id', $availableTables)
                                    ->where('online_status', 1)
                                    ->where('status', 1)
                                    ->where('no_of_seats', '<=', $person);
                            },
                        ])->where('store_id', $this->storeId)->where('is_automatic', 1)->where('status', 1)->get();

                        foreach ($sections as $section) {
                            $refTable = [];
                            $refTableTotalSeats = 0;

                            foreach ($section->tables as $table) {
                                $refTable[] = [$table->no_of_seats];
                                $refTableTotalSeats += (int) $table->no_of_seats;
                            }

//                            companionLogger('-------section wise availableTables', $refTable, $refTableTotalSeats);

                            if ($refTableTotalSeats >= $person) {

                                /*sort available tables*/
                                usort($refTable, function ($a, $b) {
                                    return $a <=> $b;
                                });
                                $match = bestsum($refTable, $person);
                                $isMatchFound = false;
                                if (! empty($match) && (array_sum($match) == $person || array_sum($match) == $person + 1)) {
                                    $isMatchFound = true;
                                } else {
                                    $match = bestsum($refTable, $person + 1);
                                    if ($match && array_sum($match) == $person + 1) {
                                        $isMatchFound = true;
                                    }
                                }

                                if ($match && $isMatchFound) {
                                    $this->pickUpSlot[$index]->disable = false;
                                    companionLogger('Slot is enable-2 | stage :- Stage7EnableDisablePickUpSlotByTableAndSmartFit', $pickTime->from_time, $person);

                                    if ($this->isTableAssign) {
                                        foreach ($match as $seat) {
                                            $temp = $section->tables->where('no_of_seats', $seat)->whereNotIn('id', $this->assignedTables[$pickTime->id] ?? 0)->first();
                                            if ($temp) {
                                                $this->assignedTables[$pickTime->id][] = $temp->id;
                                            }
                                        }
                                    }
                                    continue 2;
                                }
                            }
                        }
                    }
                }
            }
            companionLogger('-------- final slot', $this->pickUpSlot, $this->assignedTables ?? []);
        }
    }

    protected function Stage8prepareAndSendJsonResponse()
    {
        $this->returnResponseData['disable_dates'] = $this->offDayAndDate;
        $this->returnResponseData['model_name'] = $this->modelName;
        $this->returnResponseData['pickup_slot'] = collect($this->pickUpSlot)->map->only(['id', 'from_time', 'disable'])->values();
        $this->returnResponseData['error_messages'] = [];

        if ($this->isTableAssign) {
            $this->returnResponseData['assigned_tables'] = $this->assignedTables;
        }

        if (! empty($this->store->on_date_default_msg ?? null)) {
            $this->returnResponseData['error_messages']['off_day_date_error_message'] = str_replace('#PHONE#', $this->store->store_phone, $this->store->on_date_default_msg);
        }

        if (! empty($this->store->is_booking_default_msg ?? null)) {
            $this->returnResponseData['error_messages']['disable_slot'] = str_replace('#PHONE#', $this->store->store_phone, $this->store->booking_default_msg);
        }
    }

    /**
     * @param $slotModifiedData
     * @param $storeWeekDays
     *
     * @return mixed
     */
    private function getOffDates($slotModifiedData, $storeWeekDays)
    {
        $closeDayAndDates = [];
        if (count($slotModifiedData) > 0) {
            companionLogger('--------slot date wise');
//            $currentDate = $this->date;
            $currentDate = Carbon::now()->format('Y-m-d');

            $closeDates = [];
            $workingDates = [];
            foreach ($slotModifiedData as $key => $row) {
                if (empty($row['is_available'])) {
                    $closeDates[] = $row['store_date'];
                }
                if (! empty($row['is_available'])) {
                    $workingDates[] = $row['store_date'];
                }
            }
            do {
                if (in_array(Carbon::parse($currentDate)->format('Y-m-d'), $workingDates)) {
                    // all  working dates
                } elseif (! empty($closeDates) && empty($workingDates) && in_array(Carbon::parse($currentDate)->format('Y-m-d'), $closeDates)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                } elseif (! empty($closeDates) && ! empty($workingDates) && ! in_array(Carbon::parse($currentDate)->format('Y-m-d'), $workingDates)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                } elseif (empty($closeDates) && ! empty($workingDates)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                }

                $currentDate = Carbon::parse($currentDate)->addDays(1)->format('Y-m-d');
            } while ($currentDate <= $this->nextForDay);
        }

        if (count($storeWeekDays) > 0) {
            companionLogger('--------slot day wise');
//            $currentDate = $this->date;
            $currentDate = Carbon::now()->format('Y-m-d');

            $closeDays = [];
            $workingDays = [];
            foreach ($storeWeekDays as $key => $row) {
                if (empty($row['is_active'])) {
                    $closeDays[] = $row['name'];
                }
                if (! empty($row['is_active'])) {
                    $workingDays[] = $row['name'];
                }
            }

            do {
                if (in_array(Carbon::parse($currentDate)->format('l'), $workingDays)) {
                    // all working day
                } elseif (! empty($closeDays) && empty($workingDays) && in_array(Carbon::parse($currentDate)->format('l'), $closeDays)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                } elseif (! empty($closeDays) && ! empty($workingDays) && ! in_array(Carbon::parse($currentDate)->format('l'), $workingDays)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                } elseif (empty($closeDays) && ! empty($workingDays)) {
                    $closeDayAndDates[] = Carbon::parse($currentDate)->format('Y-m-d');
                }

                $currentDate = Carbon::parse($currentDate)->addDays(1)->format('Y-m-d');
            } while ($currentDate <= $this->nextForDay);
        }

        return collect($closeDayAndDates)->unique()->values();
    }
}
