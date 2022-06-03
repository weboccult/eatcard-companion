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
        if (isset($this->payload['bop']) && $this->payload['bop'] == 'wot@kiosk-tickets') {
            $this->paymentResponse['id'] = $this->createdReservation->id;
            $this->setDumpDieValue($this->paymentResponse);
        } elseif ($this->createdReservation->payment_method_type == 'ccv' || $this->createdReservation->payment_method_type == 'wipay') {
            $response = [];

            if (isset($this->paymentResponse['error']) && $this->paymentResponse['error'] == 1) {
                // Wipay will set error
                $response['error'] = $this->paymentResponse['errormsg'];
                $this->setDumpDieValue($response);
            }

            $response['payUrl'] = $this->createdReservation->payment_method_type == 'ccv' ? $this->paymentResponse['payUrl'] : null;
            $response['reservation_id'] = $this->createdReservation->reservation_id;
            $response['id'] = $this->createdReservation->id;

            $this->setDumpDieValue($response);
        } else {
            $response['error'] = 'Not supported method found.!';
            $this->setDumpDieValue($response);
            companionLogger('Not supported method found.!');
        }
    }
}
