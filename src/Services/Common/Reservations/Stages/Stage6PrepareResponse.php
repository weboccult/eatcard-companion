<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 6
 */
trait Stage6PrepareResponse
{
    protected function posResponse()
    {
//        if ($this->orderData['method'] == 'cash') {
//            if ($this->isSubOrder) {
//                $this->setDumpDieValue([
//                    'data'      => $this->parentOrder,
//                    'sub_order' => $this->createdOrder,
//                ]);
//            }
//            else {
//                $this->setDumpDieValue([
//                    'order_id' => $this->createdOrder->id,
//                    'id'       => $this->createdOrder->id,
//                    'success'  => 'success',
//                ]);
//            }
//        }
//        elseif ($this->createdOrder->payment_method_type == 'ccv' || $this->createdOrder->payment_method_type == 'wipay') {
//            if (isset($this->paymentResponse['error']) && $this->paymentResponse['error'] == 1) {
//                // Wipay will set error
//                $this->setDumpDieValue(['custom_error' => $this->paymentResponse['errormsg']]);
//            }
//            else {
//                $this->setDumpDieValue($this->paymentResponse);
//            }
//        }
//        else {
//            companionLogger('Not supported method found.!');
//        }
    }

    protected function kioskTicketsResponse()
    {
        if ($this->isBOP) {
            $this->setDumpDieValue($this->paymentResponse);
        } elseif ($this->createdReservation->payment_method_type == 'ccv' || $this->createdReservation->payment_method_type == 'wipay') {
            $this->setDumpDieValue($this->paymentResponse);
        } else {
            $response['error'] = 'Not supported method found.!';
            $this->setDumpDieValue($response);
            companionLogger('Not supported method found.!');
        }
    }
}
