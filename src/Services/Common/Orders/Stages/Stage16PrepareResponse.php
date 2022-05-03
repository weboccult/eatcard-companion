<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
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
            if (isset($this->paymentResponse['error']) && $this->paymentResponse['error'] == 1) {
                // Wipay will set error
                $this->setDumpDieValue(['custom_error' => $this->paymentResponse['errormsg']]);
            } else {
                $this->setDumpDieValue($this->paymentResponse);
            }
        } else {
            companionLogger('Not supported method found.!');
        }
    }

    protected function takeawayResponse()
    {
        if ($this->orderData['method'] == 'cash' || $this->createdOrder->is_paylater_order == 1) {
            $this->setDumpDieValue(['redirect_url' => $this->payload['url'].'?status=paid&type=takeaway&store='.$this->store->store_slug]);
        } else {
            $this->mollieAndMultiSafeResponse();
        }
    }

    protected function kioskResponse()
    {
        Session::forget('kiosk-language');
        App::setLocale('nl');

        if (isset($this->payload['bop']) && $this->payload['bop'] == 'wot@kiosk') {
            $this->setDumpDieValue($this->paymentResponse);
        } elseif ($this->createdOrder->payment_method_type == 'ccv' || $this->createdOrder->payment_method_type == 'wipay') {
            $response = [];

            if (isset($this->paymentResponse['error']) && $this->paymentResponse['error'] == 1) {
                // Wipay will set error
                $response['error'] = $this->paymentResponse['errormsg'];
            }

            $response['payUrl'] = $this->createdOrder->payment_method_type == 'ccv' ? $this->paymentResponse['payUrl'] : null;
            if (isset($this->takeawaySetting) && isset($this->takeawaySetting->print_dynamic_order_no) && (int) $this->takeawaySetting->print_dynamic_order_no > 0) {
                $response['order_id'] = ''.substr($this->createdOrder->order_id, (-1 * ((int) $this->takeawaySetting->print_dynamic_order_no)));
            } else {
                $response['order_id'] = ''.substr($this->createdOrder->order_id, -2);
            }
            $this->setDumpDieValue($response);
        } else {
            companionLogger('Not supported method found.!');
        }
    }

    protected function dineInResponse()
    {
        if (in_array($this->orderData['method'], ['cash', 'pin'])) {
            $this->setDumpDieValue($this->paymentResponse);
        } else {
            $this->mollieAndMultiSafeResponse();
        }
    }

    protected function mollieAndMultiSafeResponse(): void
    {
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
