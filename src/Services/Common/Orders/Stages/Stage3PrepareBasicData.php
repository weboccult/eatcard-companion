<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\ReservationTable;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use function Weboccult\EatcardCompanion\Helpers\carbonParseAddHoursFormat;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generateReservationId;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;

/**
 * @description Stag 3
 *
 * @author Darshit Hedpara
 */
trait Stage3PrepareBasicData
{
    protected function prepareUserId()
    {
        $this->orderData['user_id'] = auth()->id();
    }

    protected function prepareCreatedBy()
    {
        if ($this->system === SystemTypes::POS) {
            $this->orderData['created_by'] = $this->payload['pos_user_id'] ?? '';
        }
        if ($this->system === SystemTypes::WAITRESS) {
            $this->orderData['created_by'] = $this->payload['waitress_user_id'] ?? '';
        }
    }

    protected function prepareDineInType()
    {
        $this->orderData['dine_in_type'] = '';
        if ($this->system === SystemTypes::POS || $this->system === SystemTypes::DINE_IN) {
            if (isset($this->payload['dine_in_type']) && ! empty($this->payload['dine_in_type'])) {
                $this->orderData['dine_in_type'] = $this->payload['dine_in_type'];
            } else {
                if (! empty($this->storeReservation)) {
                    $this->orderData['dine_in_type'] = 'dine_in';
                } else {
                    $this->orderData['dine_in_type'] = 'take_out';
                }
            }
        }
    }

    protected function prepareOrderType()
    {
        if ($this->system === SystemTypes::POS) {
            if (empty($this->storeReservation)) {
                $this->orderData['order_type'] = 'pos';
            } else {
                if (! empty($this->storeReservation->dinein_price_id)) {
                    $this->orderData['order_type'] = 'all_you_eat';
                } else {
                    $this->orderData['order_type'] = 'dine_in';
                }
            }
        }

        if ($this->system === SystemTypes::WAITRESS || $this->system === SystemTypes::DINE_IN) {
            $this->orderData['order_type'] = $this->payload['order_type'] ?? 'dine_in';
        }

        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['is_takeaway'] = true;
            $this->orderData['order_type'] = $this->payload['order_type'];
        }
    }

    protected function prepareCashPaid()
    {
        if ($this->system === SystemTypes::POS) {
            $this->orderData['cash_paid'] = $this->payload['cash_paid'] ?? '';
        }
    }

    protected function prepareCreatedFrom()
    {
        if ($this->createdFrom != 'companion') {
            $this->orderData['created_from'] = $this->createdFrom;
        } else {
            $this->orderData['created_from'] = strtolower($this->getSystem());
        }
    }

    protected function prepareOrderStatus()
    {
        // Default
        $this->orderData['order_status'] = 'preparing';
        $this->orderData['order_date'] = Carbon::now()->format('Y-m-d');
        $this->orderData['order_time'] = Carbon::now()->format('H:i');

        // System wise condition goes here...
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS]) && (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']))) {
            $this->orderData['order_status'] = 'done';
            $this->orderData['is_picked_up'] = 1;
        }

        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['order_status'] = 'received';
            if (isset($this->payload['order_time']) && $this->payload['order_time'] == 'asap') {
                $this->orderData['order_time'] = date('H:i');
                $this->orderData['is_asap'] = 1;
            }
        }

        if ($this->system === SystemTypes::KIOSK) {
            $kiosk_data = json_decode($this->store->kiosk_data, true);
            $is_auto_preparing = $kiosk_data['is_auto_preparing'] ?? null;
            $this->orderData['order_status'] = ($is_auto_preparing) ? 'preparing' : 'received';
        }

        if ($this->system === SystemTypes::DINE_IN) {
            $butler_data = isset($this->store->StoreButler) && $this->store->StoreButler ? $this->store->StoreButler : '';
            $is_auto_preparing = $butler_data ? $butler_data->dine_in_auto_preparing : null;
            $this->orderData['order_status'] = ($is_auto_preparing) ? 'preparing' : 'received';
        }
    }

    protected function prepareOrderBasicDetails()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            if ($this->payload['order_type'] == 'pickup') {
                $this->orderData['delivery_address'] = '';
                $this->orderData['delivery_postcode'] = '';
                $this->orderData['delivery_place'] = '';
            } else {
                $this->orderData['delivery_address'] = $this->payload['delivery_street'].' '.($this->payload['house_number'] ?? '').', '.$this->payload['delivery_place'].', Netherlands';
            }
            $this->orderData['first_name'] = trim($this->payload['first_name']);
            $this->orderData['last_name'] = trim($this->payload['last_name']);
            $this->orderData['email'] = trim($this->payload['email']);
            $this->orderData['contact_no'] = trim($this->payload['contact_no']);
        }

        if ($this->system === SystemTypes::KIOSK) {
            if ($this->payload['order_type'] == 'pickup') {
                $this->orderData['delivery_address'] = '';
                $this->orderData['delivery_postcode'] = '';
                $this->orderData['delivery_place'] = '';
            }
        }

        if ($this->system === SystemTypes::DINE_IN) {
            $this->orderData['first_name'] = isset($this->payload['user_name']) && $this->payload['user_name'] ? $this->payload['user_name'] : $this->payload['name'];
            $this->orderData['contact_no'] = isset($this->payload['telephone']) && $this->payload['telephone'] ? $this->payload['telephone'] : $this->payload['name'];
            $this->orderData['method'] = $this->payload['method'] ?? 'ideal';

            //set name and mobile if guest user login and inputs don't have value
//            $is_guest_login = Session::get('dine-guest-user-login-'.$this->store->id.'-'.$this->table->id);
//            if (! empty($is_guest_login) && empty($this->payload['first_name'])) {
            if (! empty($this->table) && empty($this->payload['first_name'])) {
                $this->orderData['first_name'] = $this->payload['name'] = $this->payload['user_name'] = Session::get('dine-guest-user-name-'.$this->store->id.'-'.$this->table->id);
                $this->orderData['contact_no'] = $this->payload['telephone'] = Session::get('dine-guest-user-mobile-'.$this->store->id.'-'.$this->table->id);
            }
        }
    }

    protected function prepareSavedOrderIdData()
    {
        if ($this->system === SystemTypes::POS) {
            if (isset($this->payload['saved_order_id']) && ! empty($this->payload['saved_order_id'])) {
                $this->orderData['saved_order_id'] = $this->payload['saved_order_id'];
            }
        }
    }

    protected function createReservationForGuestAndResetSession()
    {
        if ($this->system == SystemTypes::DINE_IN && ! empty($this->table)) {
//            if (isset($this->payload['user_name']) && $this->payload['user_name']) {
//                Session::put('dine-user-name-'.$this->store->id.'-'.$this->table->id, $this->payload['user_name']);
//            }
//            $session_id = Session::get('dine-reservation-id-'.$this->store->id.'-'.$this->table->id);
            $session_id = $this->payload['reservation_id'] ?? 0;
            if (! $session_id || (! $this->storeReservation)) {
                companionLogger('start crete order for guest login user');
                companionLogger('start crete order for guest login user : session_id : ', $session_id);
                companionLogger('start crete order for guest login user : store_reservation : ', json_encode($this->storeReservation));
                if (isset($butler_data->dine_in_allow_booking) && $butler_data->dine_in_allow_booking == 0) {
                    companionLogger('reject guest order because of not allow in butler ');
                    $this->setDumpDieValue(['something_wrong_create_order' => 'Something went wrong.!']);
                }
                $seconds = time();
                $rounded_seconds = round($seconds / (15 * 60)) * (15 * 60);
                $from_time = date('H:i', $rounded_seconds);
                $store_reservation_inputs = [
                    'store_id'             => $this->store->id,
                    'reservation_id'       => generateReservationId(),
                    'res_date'             => Carbon::now()->format('Y-m-d'),
                    'res_time'             => $from_time,
                    'from_time'            => $from_time,
                    'end_time'             => carbonParseAddHoursFormat($from_time, ($this->store->butler_hour && $this->store->butler_hour != 0 ? $this->store->butler_hour : 2), 'H:i'),
                    'status'               => 'approved',
                    'voornaam'             => $this->payload['name'],
                    'is_seated'            => 1,
                    'checked_in_at'        => date('Y-m-d H:i:s'),
                    'is_dine_in'           => 1,
                    'gsm_no'               => $this->payload['telephone'] ?? '',
                    'payment_status'       => '',
                    'local_payment_status' => '',
                    'created_from'         => 'dine_in',
                    'checkin_from'         => 'dine_in',
                ];
                $this->storeReservation = StoreReservation::create($store_reservation_inputs);
                if ($this->storeReservation) {
                    ReservationTable::create([
                        'reservation_id' => $this->storeReservation->id,
                        'table_id'       => $this->table->id,
                    ]);
                }
                sendResWebNotification($this->storeReservation->id, $this->store->id, 'new_booking');
                sendResWebNotification($this->storeReservation->id, $this->store->id, 'checkin');
//                Session::put('dine-reservation-id-'.$this->store->id.'-'.$this->table->id, $this->storeReservation->id);

                //reset get user session after his reservation created / exist
//                Session::forget('dine-guest-user-login-'.$this->store->id.'-'.$this->table->id);
//                Session::forget('dine-guest-user-name-'.$this->store->id.'-'.$this->table->id);
//                Session::forget('dine-guest-user-mobile-'.$this->store->id.'-'.$this->table->id);
            }
            $this->orderData['reservation_dine_in'] = isset($this->storeReservation) ? $this->storeReservation->is_dine_in : 1;
        }
    }

    protected function prepareReservationDetails()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::WAITRESS])) {
            if (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id'])) {
                $this->orderData['parent_id'] = $this->payload['reservation_id'];
            }
            if (! empty($this->storeReservation)) {
                $this->orderData['first_name'] = $this->storeReservation->voornaam;
                $this->orderData['last_name'] = $this->storeReservation->achternaam;
                $this->orderData['all_you_eat_data'] = $this->storeReservation->all_you_eat_data;
                $table_name = [];
                if (! empty($this->storeReservation['tables'])) {
                    $table_name = $this->storeReservation->tables->pluck('table.name')->toArray();
                }
                if (! empty($table_name)) {
                    $this->orderData['table_name'] = implode(',', $table_name);
                }
            }
        }
        if ($this->system == SystemTypes::DINE_IN) {
            if ($this->storeReservation) {
                if ($this->storeReservation->is_qr_scan != 1 && $this->storeReservation->is_dine_in != 1) {
                    $this->storeReservation->update(['is_qr_scan' => 1]);
                }
//                $user_name = Session::get('dine-user-name-'.$this->store->id.'-'.$this->table->id);
                $user_name = $this->payload['name'] ?? '';
                $this->orderData['first_name'] = ! empty($user_name) ? $user_name : $this->storeReservation->voornaam;
                $this->orderData['contact_no'] = $this->storeReservation->gsm_no;
                if ($this->storeReservation->is_dine_in != 1) {
                    $this->orderData['method'] = '';
                    $this->orderData['order_type'] = 'all_you_eat';
                }

                $this->orderData['parent_id'] = $this->storeReservation->id;
                if (! empty($this->table)) {
                    $this->orderData['table_name'] = $this->table->name;
                }
            }
        }
    }
}
