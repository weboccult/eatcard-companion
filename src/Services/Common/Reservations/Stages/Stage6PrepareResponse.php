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
        if ($this->isBOP || in_array($this->createdReservation->method, ['manual_pin', 'cash'])) {
            $this->setDumpDieValue($this->paymentResponse);
        } elseif ($this->createdReservation->payment_method_type == 'ccv' || $this->createdReservation->payment_method_type == 'wipay') {
            $this->setDumpDieValue($this->paymentResponse);
        } else {
            $response['error'] = 'Not supported method found.!';
            companionLogger('Not supported method found.!');
            $this->setDumpDieValue($response);
        }
    }

    protected function kioskTicketsResponse()
    {
        if ($this->isBOP) {
            $this->setDumpDieValue($this->paymentResponse);
        } elseif ($this->createdReservation->payment_method_type == 'ccv' || $this->createdReservation->payment_method_type == 'wipay') {
            $this->setDumpDieValue($this->paymentResponse);
        } else {
            $response['error'] = 'Not supported method found.!';
            companionLogger('Not supported method found.!');
            $this->setDumpDieValue($response);
        }
    }
}
