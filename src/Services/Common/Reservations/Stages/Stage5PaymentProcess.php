<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Stages;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Enums\TransactionTypes;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\EatcardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\CashTicketsWebhook;
use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;
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
            $paymentDetails = $this->createdReservation->paymentTable()->create([
                            'transaction_type' => TransactionTypes::CREDIT,
                            'payment_method_type' => $this->createdReservation->payment_method_type,
                            'method' => $this->createdReservation->method,
                            'payment_status' => $this->createdReservation->payment_status,
                            'local_payment_status' => $this->createdReservation->local_payment_status,
                            'amount' => $this->createdReservation->total_price,
                            'kiosk_id' => $this->createdReservation->kiosk_id,
                        ]);

            $order_id = $this->createdReservation->id.'-'.$paymentDetails->id;
            companionLogger('---ccv-order-id', $order_id, $this->createdReservation);
//            $order_id = $this->createdReservation->id;

            if ($this->system == SystemTypes::POS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.reservation', [
                    'id'       => $order_id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::POS);
                $meta_data = "{'reservation': '".$this->createdReservation->id."', paymentId : '".$paymentDetails->id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.pos', [], [], SystemTypes::POS);
            }
            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.kiosk-tickets.reservation', [
                    'id'       => $order_id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::KIOSKTICKETS);
                $meta_data = "{'reservation': '".$this->createdReservation->id."', paymentId : '".$paymentDetails->id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.kiosk-tickets', [
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

            companionLogger('--CCV input parameter', $inputs);

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
            $paymentDetails->update(['transaction_id' => $response['reference']]);

            if ($this->system == SystemTypes::POS) {
                $this->paymentResponse = [
                    'pay_url'  => $response['payUrl'],
                    'reservation_id' => $this->createdReservation->id,
                    'payment_id' => $paymentDetails->id,
                ];
            }
            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $this->paymentResponse = [
                    'payUrl'  => $response['payUrl'],
                    'id' => $this->createdReservation->id,
                    'reservation_id' => $this->createdReservation->reservation_id,
                    'payment_id' => $paymentDetails->id,
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

            $paymentDetails = $this->createdReservation->paymentTable()->create([
                'transaction_type' => TransactionTypes::CREDIT,
                'payment_method_type' => $this->createdReservation->payment_method_type,
                'method' => $this->createdReservation->method,
                'payment_status' => $this->createdReservation->payment_status,
                'local_payment_status' => $this->createdReservation->local_payment_status,
                'amount' => $this->createdReservation->total_price,
                'kiosk_id' => $this->createdReservation->kiosk_id,
            ]);

            $inputs = [
                'amount'    => $order_price,
                'terminal'  => $this->device->terminal_id,
                'reference' => $this->createdReservation->id,
                'txid'      => $order_type.'-'.$this->createdReservation->id.'-'.$this->createdReservation->store_id.'-'.($paymentDetails->id ?? 0),
            ];

            companionLogger('Wipay payment details', $inputs);
            companionLogger('Wipay started!', 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

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
                companionLogger('Wipay payment initialize error', $response);
                $this->setDumpDieValue(['error' => $response['errormsg']]);
            }

            companionLogger('Wipay payment Response', $response, 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));

            isset($response['ssai']) && $response['ssai'] ? $paymentDetails->update(['transaction_id' => $response['ssai']]) : '';

            $this->paymentResponse = [
                'pay_url'  => null,
                'id' => $this->createdReservation->id,
                'reservation_id' => $this->createdReservation->reservation_id,
                'payment_id' => $paymentDetails->id,
            ];
        }
    }

    protected function cashPayment()
    {
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            if ($this->isBOP) {
                $paymentDetails = $this->createdReservation->paymentTable()->create([
                    'transaction_type' => TransactionTypes::CREDIT,
                    'payment_method_type' => $this->createdReservation->payment_method_type,
                    'method' => $this->createdReservation->method,
                    'payment_status' => $this->createdReservation->payment_status,
                    'local_payment_status' => $this->createdReservation->local_payment_status,
                    'amount' => $this->createdReservation->total_price,
                    'kiosk_id' => $this->createdReservation->kiosk_id,
                    'transaction_receipt' => 'fake-bop payment',
                ]);

                $this->paymentResponse = [
                    'ssai'      => 'fake_ssai',
                    'reference' => 'fake_response',
                    'payUrl'    => 'https://www.google.com',
                    'id' => $this->createdReservation->id,
                    'reservation_id' => $this->createdReservation->reservation_id,
                    'payment_id' => $paymentDetails->id,
                ];

                EatcardWebhook::action(CashTicketsWebhook::class)
                    ->setOrderType('reservation')
                    ->setReservationId($this->createdReservation->id)
                    ->setPaymentId($paymentDetails->id)
                    ->setStoreId($this->store->id)
                    ->payload([
                        'status' => $this->payload['bop_status'] ?? 'paid',
                    ])->dispatch();
            }
        }
    }
}
