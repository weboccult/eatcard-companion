<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\CancelReservation;
use Weboccult\EatcardCompanion\Models\ReservationJob;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\EatcardReservationTableAssign;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTableAssign\KioskTickets\KioskTickets;
use function Weboccult\EatcardCompanion\Helpers\checkAnotherMeeting;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\assignedReservationTableOrUpdate;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @description Stag 4
 */
trait Stage4CreateProcess
{
    protected function isSimulateEnabled()
    {
        if ($this->getSimulate()) {
            $this->setDumpDieValue([
                'reservation_data' => $this->reservationData,
            ]);
        }
    }

    protected function createReservation()
    {
        companionLogger('--- Reservation create data : ', $this->reservationData);
        $this->reservationData['all_you_eat_data'] = json_encode($this->reservationData['all_you_eat_data']);
        $this->createdReservation = StoreReservation::query()->create($this->reservationData);
        $this->createdReservation->refresh();
    }

    protected function tableAvailabilityCheck()
    {
        if ($this->system == SystemTypes::POS) {
            $tables = $this->payload['table_ids'] ?? [];
            $tableAvailabilityCheck = checkAnotherMeeting($tables, $this->createdReservation);
            if ($tableAvailabilityCheck) {
                $this->setDumpDieValue(['error' => 'Currently, this table is available please try another one']);
            }
        }
    }

    protected function assignedTables()
    {
        if ($this->system == SystemTypes::POS) {
            $tables = $this->payload['table_ids'] ?? [];
            $isTableAssigned = assignedReservationTableOrUpdate($this->createdReservation, $tables);

            /*<--- if reservation table assigned successfully then send notification --->*/
            if (! empty($this->createdReservation) && $isTableAssigned) {
                sendResWebNotification($this->createdReservation->id, $this->createdReservation->store_id);
            }
        }
    }

    protected function createReservationJob()
    {
        if ($this->system == SystemTypes::POS) {
            return;
        }

        if (empty($this->createdReservation)) {
            return;
        }

        $this->reservationData['data_model'] = $this->slotType;
        $this->reservationData['store_slug'] = $this->payload['store_slug'] ?? '';

        $this->reservationJobData['store_id'] = $this->store->id;
        $this->reservationJobData['reservation_id'] = $this->createdReservation->id;
        $this->reservationJobData['attempt'] = 0;
        $this->reservationJobData['reservation_front_data'] = (json_encode($this->reservationData, true));

        /*create reservation entry on reservation jobs table*/
        $this->isReservationCronStop = false;
        $first_reservation = ReservationJob::query()->where('attempt', 0)
            ->where('in_queue', 0)
            ->where('is_completed', 0)
            ->where('is_failed', 0)
            ->first();
        if (! empty($first_reservation)) {
            $first_reservation = ReservationJob::query()->where('attempt', 2)
                ->where('in_queue', 0)
                ->where('is_completed', 0)
                ->where('is_failed', 1)
                ->first();
        }
        $time_difference = 0;
        if (isset($first_reservation->created_at)) {
            $current_time = Carbon::now();
            $end_time = Carbon::parse($first_reservation->created_at);
            $time_difference = $current_time->diffInSeconds($end_time);
        }
        if ($time_difference > 90) {
            ReservationJob::query()->whereNotNull('id')->update(['is_failed'=> 1, 'attempt' => 2]);
            $this->isReservationCronStop = true;
            companionLogger('reservation through normal functionality : ', (['reservation_job_first_res'=>$first_reservation]));
        }
        /*<---- for testing manually cron stop using this variable ---->*/
        if (env('FORCE_STOP_CREATE_RESERVATION_USING_CRON', false)) {
            $this->isReservationCronStop = true;
            companionLogger('Manually cron skip for testing if you want to stop this remove env FORCE_STOP_CREAT_RESERVATION_USING_CRON variable or make it FALSE');
        }

        $this->createdReservationJobs = ReservationJob::query()->create($this->reservationJobData);
        $this->createdReservationJobs->refresh();
    }

    protected function assignTableIfCronStop()
    {
        if (! $this->isReservationCronStop || empty($this->createdReservationJobs)) {
            return;
        }
        try {
            companionLogger('Manual table assign start --------');
            EatcardReservationTableAssign::action(KioskTickets::class)
                        ->setStoreId($this->store->id)
                        ->setReservationId($this->createdReservation->id)
                        ->setMealId($this->meal->id)
                        ->setDate($this->reservationDate)
                        ->setPayload([
                            'slot_id' => $this->createdReservation->slot_id,
                            'data_model' => $this->createdReservation->slot_model,
                            'person' => $this->createdReservation->person,
                            'reservation_job_id' => $this->createdReservationJobs->id,
                            'reservation_check_attempt' => $this->createdReservationJobs->attempt,
                            'reservation_front_data' => $this->createdReservationJobs->reservation_front_data,
                        ])
                        ->dispatch();

            // TODO : add loop for handel 3 attempt
        } catch (\Exception $e) {
            companionLogger('Tickets Create reservation manual table assign error :', 'Error : '.$e->getMessage(), 'Line : '.$e->getLine(), 'File : '.$e->getFile(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));

            ReservationJob::where('id', $this->createdReservationJobs->id)->delete();

            CancelReservation::create([
                'reservation_id'         => $this->createdReservation->id,
                'store_id'               => $this->store->id,
                'reservation_front_data' => $this->createdReservationJobs->reservation_front_data,
                'reason'                 => 'Failed',
            ]);
        }
    }

    protected function checkReservationJobForAssignTableStatus()
    {
        if ($this->system == SystemTypes::POS) {
            return;
        }

        if (empty($this->createdReservationJobs)) {
            return;
        }

        /*get reservation status*/
        $check_res_status_array = [1, 2, 3, 4, 5];
        for ($i = 0; $i < 5; $i++) {
            $reservation_jobs_count = ReservationJob::query()->where('id', $this->createdReservationJobs->id)->count();
            if ($reservation_jobs_count > 0) {
                sleep($check_res_status_array[$i]);
            } else {
                break;
            }
        }
    }

    protected function checkReservationForAssignTableStatus()
    {
        if ($this->system == SystemTypes::POS) {
            return;
        }

        $count = 1;
        do {
            $storeReservation = StoreReservation::query()->where('id', $this->createdReservation->id)->first();
            if ($storeReservation->res_status != null) {
                $count = 4;
            } else {
                sleep(3);
                $count++;
            }
        } while (($storeReservation->res_status == null || $storeReservation->res_status == '') && $count <= 3);

        if (empty($storeReservation->res_status) || $storeReservation->res_status == 'failed') {
            companionLogger('res_status is null', $storeReservation->res_status);
            StoreReservation::where('id', $storeReservation->id)->update(['status'=> 'declined', 'is_manually_cancelled' => 2]);
            $this->setDumpDieValue(['error' =>'Sorry selected slot is not available.Please try another time slot']);
        }

        //if reservation table assigned successfully then send notification
        sendResWebNotification($this->createdReservation->id, $this->createdReservation->store_id);
    }

    protected function createChatThread()
    {
        if ($this->system == SystemTypes::POS) {
            return;
        }

        $thread = Thread::query()->create([
            'subject' => 'reservation',
        ]);
        $this->createdReservation->update(['thread_id' => $thread->id]);
        $ownerId = ($this->store) ? $this->store->created_by : 0;
        $owner = $this->store->store_owner->where('store_id', $this->store->store_id)->first();
        if ($owner) {
            $ownerId = $owner->user_id;
        }
        Participant::query()->insert([
            [
                'thread_id'  => $thread->id,
                'user_id'    => $this->createdReservation->user_id ?? 0,
                'last_read'  => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'thread_id'  => $thread->id,
                'user_id'    => $ownerId,
                'last_read'  => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
