<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generalUrlGenerator;
use function Weboccult\EatcardCompanion\Helpers\phpEncrypt;
use function Weboccult\EatcardCompanion\Helpers\webhookGenerator;

/**
 * @description Stag 5
 * @mixin BaseProcessor
 */
trait Stage5PaymentProcess
{
    /**
     * @throws GuzzleException
     *
     * @return void
     */
    protected function ccvPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS]) && $this->createdReservation->payment_method_type == 'ccv') {
            if ($this->system == SystemTypes::POS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.reservation', [
                    'id'       => $this->createdReservation->id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::POS);
                $meta_data = "{'reservation': '".$this->createdReservation->reservation_id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.pos', [], [], SystemTypes::POS);
            }
            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.kiosk.reservation', [
                    'id'       => $this->createdReservation->id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::KIOSKTICKETS);
                $meta_data = "{'reservation': '".$this->createdReservation->order_id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.reservation', [
                    'device_id' => phpEncrypt($this->device->id),
                ], [], SystemTypes::KIOSKTICKETS);
            }
            $inputs = [
                'amount'     => number_format((float) $this->createdReservation->total_price, 2, '.', ''),
                'currency'   => 'EUR',
                'method'     => 'TERMINAL',
                'returnUrl'  => $returnUrl,
                'webhookUrl' => $webhook_url,
                'language'   => 'NLD',
                'metadata'   => $meta_data,
                'details'    => [
                    'port'               => $this->device->port,
                    'terminalId'         => $this->device->terminal_id,
                    'managementSystemId' => $this->device->management_system_id,
                    'ip'                 => $this->device->ip_address,
                    'accessProtocol'     => 'OPI_NL',
                ],
            ];
            $client = new Client();

            $url = $this->device->environment == 'test' ? config('eatcardCompanion.payment.gateway.ccv.staging') : config('eatcardCompanion.payment.gateway.ccv.production');
            $createOrderUrl = config('eatcardCompanion.payment.gateway.ccv.endpoints.createOrder');

            $kiosk_api_key = $this->device->environment == 'test' ? $this->device->test_api_key : $this->device->api_key;
            $api_key = base64_encode($kiosk_api_key.':');

            $request = $client->request('POST', $url.$createOrderUrl, [
                'headers' => [
                    'Authorization' => 'Basic '.$api_key,
                    'Content-Type'  => 'application/json;charset=UTF-8',
                ],
                'body'    => json_encode($inputs, true),
            ]);
            $request->getHeaderLine('content-type');
            $response = json_decode($request->getBody()->getContents(), true);
            companionLogger('ccv api res', $response);
            /*update ccv payment for the order*/
            $this->createdReservation->update(['ccv_payment_ref' => $response['reference']]);

            if ($this->system == SystemTypes::POS) {
                $this->paymentResponse = [
                    'pay_url'  => $response['payUrl'],
                    'reservation_id' => $this->createdReservation->id,
                ];
            }
            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $this->paymentResponse = [
                    'payUrl'  => $response['payUrl'],
                    'reservation_id' => $this->createdReservation->id,
                ];
            }
        }
    }

    /**
     * @throws GuzzleException
     *
     * @return void
     */
    protected function wiPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS]) && $this->createdReservation->payment_method_type == 'wipay') {
            $order_price = round($this->createdReservation->total_price * 100, 0);
            $order_type = 'reservation';

            $inputs = [
                'amount'    => $order_price,
                'terminal'  => $this->device->terminal_id,
                'reference' => $this->createdReservation->id,
                'txid'      => $order_type.'-'.$this->createdReservation->id.'-'.$this->createdReservation->store_id.'-'.(@$this->payload['is_last_payment'] ? '1' : '0'),
            ];

            companionLogger('Wipay payment details', $inputs);
            companionLogger('wipay started!', 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

            $client = new Client();
            $url = $this->device->environment == 'live' ? config('eatcardCompanion.payment.gateway.wipay.production') : config('eatcardCompanion.payment.gateway.wipay.staging');
            $createOrderUrl = config('eatcardCompanion.payment.gateway.wipay.endpoints.createOrder');

            $request_data = $client->request('POST', $url.$createOrderUrl, [
                'headers' => ['Content-Type' => 'application/json;charset=UTF-8'],
                'cert'    => public_path('worldline/eatcard.nl.pem'),
                'ssl_key' => public_path('worldline/eatcard.nl.key'),
                'body'    => json_encode($inputs, true),
            ]);

            $request_data->getHeaderLine('content-type');
            $response = json_decode($request_data->getBody()->getContents(), true);

            if ($response['error'] == 1) {
                $this->paymentResponse = $response;

                return;
            }

            companionLogger('Wipay payment Response', 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));

            isset($response['ssai']) && $response['ssai'] ? $this->createdReservation->update(['worldline_ssai' => $response['ssai']]) : '';

            $this->paymentResponse = [
                'pay_url'  => null,
                'reservation_id' => $this->createdReservation->id,
            ];
        }
    }

    protected function cashPayment()
    {
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            if (isset($this->payload['bop']) && $this->payload['bop'] == 'wot@kiosk-tickets') {
                $this->paymentResponse = [
                    'ssai'      => 'fake_ssai',
                    'reference' => 'fake_response',
                    'payUrl'    => 'https://www.google.com',
                ];
            }
        }
    }
}
