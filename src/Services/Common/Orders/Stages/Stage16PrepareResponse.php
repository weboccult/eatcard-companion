<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 16
 *
 * @author Darshit Hedpara
 */
trait Stage16PrepareResponse
{
    protected function posResponse()
    {
        if ($this->orderData['method'] == 'cash') {
            if ($this->isSubOrder) {
                $this->setDumpDieValue([
                    'data'      => $this->parentOrder,
                    'sub_order' => $this->createdOrder,
                ]);
            } else {
                $this->setDumpDieValue([
                    'order_id' => $this->createdOrder->id,
                    'id'       => $this->createdOrder->id,
                    'success'  => 'success',
                ]);
            }
        } elseif ($this->createdOrder->payment_method_type == 'ccv' || $this->createdOrder->payment_method_type == 'wipay') {
            $this->setDumpDieValue($this->paymentResponse);
        } else {
            companionLogger('Not supported method found.!');
        }
    }

    protected function takeawayResponse()
    {
        if ($this->orderData['method'] == 'cash') {
            $this->setDumpDieValue(['redirect_url' => $this->payload['url'].'?status=paid&type=takeaway&store='.$this->store->store_slug]);
        } else {
            if ($this->createdOrder->payment_method_type == 'mollie') {
                if (isset($this->paymentResponse['payment_link'])) {
                    $this->setDumpDieValue(['payment_link' => $this->paymentResponse['payment_link']]);
                } elseif (isset($this->paymentResponse['mollie_error'])) {
                    $this->setDumpDieValue(['error_from_mollie_payment_gateway' => $this->paymentResponse['mollie_error']]);
                } else {
                    $this->setDumpDieValue(['error_from_mollie_payment_gateway' => 'Something went wrong']);
                }
            } elseif ($this->createdOrder->payment_method_type == 'multisafepay') {
                if (isset($this->paymentResponse['payment_url'])) {
                    $this->setDumpDieValue(['payment_link' => $this->paymentResponse['payment_url']]);
                } else {
                    if (isset($this->paymentResponse['multisafe_error_message'])) {
                        $this->setDumpDieValue(['error_from_multisafe_payment_gateway' => $this->paymentResponse['multisafe_error_message']]);
                    } else {
                        $this->setDumpDieValue(['error_from_multisafe_payment_gateway' => 'Something went wrong']);
                    }
                }
            } else {
                companionLogger('Not supported payment method found.!');
            }
        }
    }
}
