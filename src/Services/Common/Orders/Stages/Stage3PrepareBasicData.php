<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;

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
    }

    protected function prepareDineInType()
    {
        $this->orderData['dine_in_type'] = '';
        if ($this->system === SystemTypes::POS) {
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
        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['order_type'] = $this->payload['order_type'];
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

        if ($this->system === SystemTypes::TAKEAWAY) {
            $this->orderData['is_takeaway'] = true;
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
        if ($this->system === SystemTypes::POS && (isset($this->payload['reservation_id']) && ! empty($this->payload['reservation_id']))) {
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
    }

    protected function prepareOrderBasicDetails()
    {
        if ($this->system === SystemTypes::POS) {
            $this->orderData['created_by'] = $this->payload['pos_user_id'];
        }

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
    }

    protected function prepareSavedOrderIdData()
    {
        if ($this->system === SystemTypes::POS) {
            if (isset($this->payload['saved_order_id']) && ! empty($this->payload['saved_order_id'])) {
                $this->orderData['saved_order_id'] = $this->payload['saved_order_id'];
            }
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
    }
}
