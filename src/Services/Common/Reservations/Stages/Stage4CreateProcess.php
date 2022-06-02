<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;
use Weboccult\EatcardCompanion\Models\ReservationJob;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
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
        $this->createdReservation = StoreReservation::query()->create($this->reservationData);
        $this->createdReservation->refresh();
    }

    protected function createReservationJob()
    {
        if (empty($this->createdReservation)) {
            return;
        }

        $this->createdReservation['data_model'] = $this->slotType;

        $this->reservationJobData['store_id'] = $this->store->id;
        $this->reservationJobData['reservation_id'] = $this->createdReservation->id;
        $this->reservationJobData['attempt'] = 0;
        $this->reservationJobData['reservation_front_data'] = (json_encode($this->createdReservation, true));

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
        if (! $this->isReservationCronStop) {
            return;
        }

        // TODO : add manual cron assign table code here
    }

    protected function checkReservationJobForAssignTableStatus()
    {
        if ($this->isReservationCronStop) {
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
        $count = 1;
        do {
            companionLogger('do while start');

            $storeReservation = StoreReservation::query()->where('id', $this->createdReservation->id)->first();

            if ($storeReservation->res_status != null) {
                $count = 4;
            } else {
                sleep(3);
                $count++;
            }
        } while (($storeReservation->res_status == null || $storeReservation->res_status == '') && $count <= 3);

        companionLogger('do while end');

        if (empty($storeReservation->res_status) || $storeReservation->res_status == 'failed') {
            companionLogger('res_status is null');
            StoreReservation::where('id', $storeReservation->id)->update(['status'=> 'declined', 'is_manually_cancelled' => 2]);
            $this->setDumpDieValue([
                'status'  => 'error',
                'message' => 'Sorry selected slot is not available.Please try another time slot',
                'code'    => 400,
            ]);
        }

        //if reservation table assigned successfully then send notification
        sendResWebNotification($this->createdReservation->id, $this->createdReservation->store_id);
    }

    protected function createChatThread()
    {
        $thread = Thread::query()->create([
            'subject' => 'reservation',
        ]);
        $this->createdReservation->update(['thread_id' => $thread->id]);
        $ownerId = ($this->store) ? $this->store->created_by : 0;
        $owner = $this->store->store_owner->where('store_id', is->store->store_id)->first();
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
