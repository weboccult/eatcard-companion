<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\reverseRouteGenerator;

/**
 * @description Stag 12
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage12PaymentProcess
{
    /**
     * @throws GuzzleException
     *
     * @return void
     */
    protected function ccvPayment()
    {
        if ($this->system == SystemTypes::POS && $this->orderData['method'] == 'ccv') {
            if ($this->isSubOrder) {
                $webhook_url = reverseRouteGenerator('payment.webhook.pos.sub_order', [
                    'id'       => $this->createdOrder->id,
                    'store_id' => $this->store->id,
                ], ['is_last_payment' => $this->payload['is_last_payment'] ?? 0], SystemTypes::POS);
                $meta_data = "{'parent_order': '".$this->createdOrder->parent_order_id."','sub_order': '".$this->createdOrder->id."'}";
            } else {
                $webhook_url = reverseRouteGenerator('payment.webhook.pos.order', [
                    'id'       => $this->createdOrder->id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::POS);
                $meta_data = "{'order': '".$this->createdOrder->order_id."'}";
            }
            $inputs = [
                'amount'     => number_format((float) $this->createdOrder->total_price, 2, '.', ''),
                'currency'   => 'EUR',
                'method'     => 'TERMINAL',
                'returnUrl'  => reverseRouteGenerator('payment.returnUrl.pos', [], SystemTypes::POS),
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
            $debit = config('eatcardCompanion.payment.gateway.ccv.endpoints.debit');

            $kiosk_api_key = $this->device->environment == 'test' ? $this->device->test_api_key : $this->device->api_key;
            $api_key = base64_encode($kiosk_api_key.':');

            $request = $client->request('POST', $url.$debit, [
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
            if (isset($order['order_id'])) {
                unset($order->order_id);
            }
            $this->createdOrder->update(['ccv_payment_ref' => $response['reference']]);
            if ($this->isSubOrder) {
                $this->paymentResponse = [
                    'pay_url'      => $response['payUrl'],
                    'sub_order_id' => $this->createdOrder->id,
                    'order_id'     => $this->createdOrder->id,
                ];
            } else {
                $this->paymentResponse = [
                    'pay_url'  => $response['payUrl'],
                    'order_id' => $this->createdOrder->id,
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
        if ($this->system == SystemTypes::POS && $this->orderData['method'] == 'wipay') {
            $order_price = round($this->createdOrder->total_price * 100, 0);
            $order_type = $this->isSubOrder ? 'sub_order' : 'order';

            $inputs = [
                'amount'    => $order_price,
                'terminal'  => $this->device->terminal_id,
                'reference' => $this->createdOrder->id,
                'txid'      => $order_type.'-'.$this->createdOrder->id.'-'.$this->createdOrder->store_id.'-'.(@$this->payload['is_last_payment'] ? '1' : '0'),
            ];

            companionLogger('Wipay payment details', $inputs);
            companionLogger('wipay started!', 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

            $client = new Client();
            $url = $this->device->environment == 'live' ? config('eatcardCompanion.payment.gateway.wipay.production') : config('eatcardCompanion.payment.gateway.wipay.staging');
            $debit = config('eatcardCompanion.payment.gateway.wipay.endpoints.debit');

            $request_data = $client->request('POST', $url.$debit, [
                'headers' => ['Content-Type' => 'application/json;charset=UTF-8'],
                'cert'    => public_path('worldline/eatcard.nl.pem'),
                'ssl_key' => public_path('worldline/eatcard.nl.key'),
                'body'    => json_encode($inputs, true),
            ]);

            $request_data->getHeaderLine('content-type');
            $response = json_decode($request_data->getBody()->getContents(), true);

            if ($response['error'] == 1) {
                $this->setDumpDieValue(['custom_error' => $response['errormsg']]);
            }

            companionLogger('Wipay payment Response', 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));

            if ($order_type == 'sub_order') {
                $order = SubOrder::query()->findOrFail($this->createdOrder->id);
            } else {
                $order = Order::query()->findOrFail($this->createdOrder->id);
            }

            isset($response['ssai']) && $response['ssai'] ? $order->update(['worldline_ssai' => $response['ssai']]) : '';

            $data = [
                'pay_url'  => null,
                'order_id' => $order['id'],
            ];

            if ($this->isSubOrder) {
                $this->paymentResponse = [
                   'sub_order' => $this->createdOrder,
                   'data'      => $data,
               ];
            } else {
                $this->paymentResponse = $data;
            }
        }
    }

    protected function molliePayment()
    {
        try {
            Mollie::api()->setApiKey($this->store->mollie_api_key);
            $redirectUrl = reverseRouteGenerator('payment.returnUrl.takeaway', [
                'id'       => $this->createdOrder->id,
                'store_id' => $this->store->id,
            ], ['url' => $this->payload['url']], SystemTypes::TAKEAWAY);

            $webhookUrl = reverseRouteGenerator('payment.webhook.takeaway', [
                'id'       => $this->createdOrder->id,
                'store_id' => $this->store->id,
            ], [], SystemTypes::TAKEAWAY);

            $payment = Mollie::api()->payments()->create([
                'amount'      => [
                    'currency' => 'EUR',
                    'value'    => ''.number_format($this->createdOrder->total_price, 2, '.', ''),
                ],
                'method'      => $this->orderData['method'],
                'description' => 'Order #'.$this->createdOrder->order_id,
                'redirectUrl' => $redirectUrl,
                'webhookUrl'  => $webhookUrl,
                'metadata'    => [
                    'order_id' => $this->createdOrder->order_id,
                ],
            ]);
            $this->createdOrder->update(['mollie_payment_id' => $payment->id]);
            $this->paymentResponse = [
                'payment_link' => $payment->_links->checkout->href,
            ];
        } catch (Exception $e) {
            if ($e->getCode() > 400) {
                if (strpos($e->getMessage(), 'The amount is lower than the minimum')) {
                    $this->paymentResponse = [
                        'mollie_error' => 'Something went wrong from the payment gateway | Error : The amount is lower than the minimum.',
                    ];
                } else {
                    $this->paymentResponse = [
                        'mollie_error' => 'Something went wrong from the payment gateway',
                    ];
                }
            } else {
                $this->paymentResponse = [
                    'mollie_error' => 'Something went wrong from the payment gateway',
                ];
            }
        }
    }

    protected function multiSafePayment()
    {
    }

    protected function updateOrderReferenceIdFromPaymentGateway()
    {
    }

    protected function setBypassPaymentLogicAndOverridePaymentResponse()
    {
    }
}
