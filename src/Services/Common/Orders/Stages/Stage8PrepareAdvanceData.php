<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\generatePOSOrderId;

/**
 * @description Stag 8
 * @mixin BaseProcessor
 */
trait Stage8PrepareAdvanceData
{
    /**
     * @return void
     */
    protected function prepareOrderDiscount()
    {
        if ($this->system == SystemTypes::POS) {
            if (! empty($this->storeReservation)) {
                if (isset($this->storeReservation->discount_type) && isset($this->storeReservation->discount) && ! empty($this->storeReservation->discount) && (float) $this->storeReservation->discount > 0) {
                    $this->discountData['order_discount'] = $this->payload['order_discount'] = (float) $this->storeReservation->discount;
                    $this->discountData['is_euro_discount'] = $this->payload['is_euro_discount'] = $this->storeReservation->discount_type == 'EURO' ? 1 : 0;
                }
            }
        }
    }

    protected function preparePaymentMethod()
    {
        $this->orderData['method'] = $this->payload['method'];
    }

    protected function preparePaymentDetails()
    {
        if (in_array($this->system, [SystemTypes::KIOSK, SystemTypes::POS, SystemTypes::WAITRESS])) {
            if ($this->orderData['method'] == 'cash') {
                $this->orderData['status'] = 'paid';
                $this->orderData['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
                $this->orderData['payment_method_type'] = '';
            } else {
                $this->orderData['method'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
                $this->orderData['payment_method_type'] = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
            }
            if ($this->system == SystemTypes::POS) {
                if (isset($this->payload['is_split_payment']) && $this->payload['is_split_payment'] == 1) {
                    $this->orderData['status'] = 'pending';
                    $this->orderData['paid_on'] = null;
                }
            }
        }
    }

    protected function prepareOrderId()
    {
        if ($this->system == SystemTypes::POS) {
            $order = null;
            if (isset($order_data['parent_id'])) {
                $order = Order::query()->where('parent_id', $order_data['parent_id'])->first();
            }
            $this->orderData['order_id'] = (! empty($order)) ? $order->order_id : generatePOSOrderId($order_data['store_id']);
        }
    }

    protected function prepareOrderDetails()
    {
        $this->orderData['sub_total'] = 0;
        $this->orderData['alcohol_sub_total'] = 0;
        $this->orderData['normal_sub_total'] = 0;
        $this->orderData['total_tax'] = 0;
        $this->orderData['total_alcohol_tax'] = 0;
        $this->orderData['total_price'] = 0;
        $this->orderData['discount_amount'] = 0;
        $this->orderData['is_takeaway_mail_send'] = 0;
        $this->orderData['discount_inc_tax'] = 0;
        $this->orderData['statiege_deposite_total'] = 0;
    }

    protected function prepareSupplementDetails()
    {
    }

    protected function prepareOrderItemsDetails()
    {
    }

    protected function prepareAyceAmountDetails()
    {
        if (isset($this->payload['ayce_amount']) && $this->payload['ayce_amount']) {
            $product_total = $this->payload['ayce_amount'];
            $this->orderData['ayce_price'] = $this->payload['ayce_amount'];
            $current_sub = ($product_total * 9 / 109);
            $this->orderData['normal_sub_total'] += $product_total - $current_sub;
            $this->orderData['total_tax'] += $current_sub;
            $this->orderData['total_price'] += $product_total;
        }
    }

    protected function prepareEditOrderDetails()
    {
        if ($this->system == SystemTypes::POS) {
            if (isset($this->payload['edited']) && $this->payload['edited'] == 1) {
                $this->orderData['is_edited'] = 1;
                $this->orderData['edited_by'] = $this->payload['edited_by'] ?? '';
                $this->orderData['ref_id'] = $this->payload['ref_id'] ?? '';
                $this->orderData['is_base_order'] = 0;
            }
        }
    }

    protected function prepareUndoOrderDetails()
    {
    }

    protected function prepareCouponDetails()
    {
    }
}
