<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Queue;
use GuzzleHttp\Exception\GuzzleException;
use Mollie\Laravel\Facades\Mollie;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\DineinCart;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\SubOrder;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use Weboccult\EatcardCompanion\Services\Facades\EatcardPrint;
use Weboccult\EatcardCompanion\Services\Facades\MultiSafe;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generalUrlGenerator;
use function Weboccult\EatcardCompanion\Helpers\phpEncrypt;
use function Weboccult\EatcardCompanion\Helpers\webhookGenerator;

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
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSK]) && $this->createdOrder->payment_method_type == 'ccv' && ! $this->settings['bop_kiosk']['status']) {
            companionLogger('----ccv payment start ', ['order_id' => $this->createdOrder->id]);
            if ($this->system == SystemTypes::POS) {
                if ($this->isSubOrder) {
                    $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.sub_order', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['is_last_payment' => $this->payload['is_last_payment'] ?? 0], SystemTypes::POS);
                    $meta_data = "{'parent_order': '".$this->createdOrder->parent_order_id."','sub_order': '".$this->createdOrder->id."'}";
                } else {
                    $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.order', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], [], SystemTypes::POS);
                    $meta_data = "{'order': '".$this->createdOrder->order_id."'}";
                }
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.pos', [], [], SystemTypes::POS);
            }
            if ($this->system == SystemTypes::KIOSK) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.kiosk.order', [
                    'id'       => $this->createdOrder->id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::KIOSK);
                $meta_data = "{'order': '".$this->createdOrder->order_id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.kiosk', [
                    'device_id' => phpEncrypt($this->device->id),
                ], [], SystemTypes::KIOSK);
            }
            $inputs = [
                'amount'     => number_format((float) $this->createdOrder->total_price, 2, '.', ''),
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

            companionLogger('----ccv payment payload ', $inputs, ['order_id' => $this->createdOrder->id]);

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
            companionLogger('ccv api response', $response, ['order_id' => $this->createdOrder->id]);
            /*update ccv payment for the order*/
            if (isset($order['order_id'])) {
                unset($order->order_id);
            }
            $this->createdOrder->update(['ccv_payment_ref' => $response['reference']]);

            if ($this->system == SystemTypes::POS) {
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
            if ($this->system == SystemTypes::KIOSK) {
	            if (isset($this->takeawaySetting) && isset($this->takeawaySetting->print_dynamic_order_no) && (int) $this->takeawaySetting->print_dynamic_order_no > 0) {
		            $orderId = ''.substr($this->createdOrder->order_id, (-1 * ((int)$this->takeawaySetting->print_dynamic_order_no)));
	            } else {
		            $orderId = ''.substr($this->createdOrder->order_id, -2);
	            }
                $this->paymentResponse = [
                    'payUrl'  => $response['payUrl'],
                    'order_id' => $this->createdOrder->id,
                    'order_number' => $orderId
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
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSK]) && $this->createdOrder->payment_method_type == 'wipay' && ! $this->settings['bop_kiosk']['status']) {
            $order_price = round($this->createdOrder->total_price * 100, 0);
            if ($this->system == SystemTypes::KIOSK) {
                $order_type = 'order';
            }
            if ($this->system == SystemTypes::POS) {
                $order_type = $this->isSubOrder ? 'sub_order' : 'order';
            }

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

            companionLogger('Wipay payment Response', $this->paymentResponse, 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));

            if ($this->system == SystemTypes::POS && $this->isSubOrder && $order_type == 'sub_order') {
                $order = SubOrder::query()->findOrFail($this->createdOrder->id);
            } else {
                $order = Order::query()->findOrFail($this->createdOrder->id);
            }

            isset($response['ssai']) && $response['ssai'] ? $order->update(['worldline_ssai' => $response['ssai']]) : '';

            $data = [
                'pay_url'  => null,
                'order_id' => $order['id'],
            ];

            if ($this->system == SystemTypes::POS && $this->isSubOrder) {
                $this->paymentResponse = [
                   'sub_order' => $this->createdOrder,
                   'data'      => $data,
               ];
            } else {
	            if (isset($this->takeawaySetting) && isset($this->takeawaySetting->print_dynamic_order_no) && (int) $this->takeawaySetting->print_dynamic_order_no > 0) {
		            $orderId = ''.substr($this->createdOrder->order_id, (-1 * ((int)$this->takeawaySetting->print_dynamic_order_no)));
	            } else {
		            $orderId = ''.substr($this->createdOrder->order_id, -2);
	            }
	            $data['order_number'] = $orderId;
                $this->paymentResponse = $data;
            }
        }
    }

    protected function molliePayment()
    {
        if (in_array($this->system, [SystemTypes::TAKEAWAY, SystemTypes::DINE_IN]) && $this->createdOrder->payment_method_type == 'mollie') {
            try {
                Mollie::api()->setApiKey($this->store->mollie_api_key);

                if ($this->system === SystemTypes::TAKEAWAY) {
                    $redirectUrl = generalUrlGenerator('payment.gateway.mollie.redirectUrl.takeaway', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['url' => $this->payload['url']], SystemTypes::TAKEAWAY);

                    $webhookUrl = webhookGenerator('payment.gateway.mollie.webhook.takeaway', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], [], SystemTypes::TAKEAWAY);
                }
                if ($this->system === SystemTypes::DINE_IN) {
                    if (! empty($this->storeReservation)) {
                        $redirectUrl = generalUrlGenerator('payment.gateway.mollie.redirectUrl.dine_in', [
                            'id'       => $this->createdOrder->id,
                            'store_id' => $this->store->id,
                        ], ['url' => $this->payload['url'], 'res_id' => $this->storeReservation->id], SystemTypes::DINE_IN);

                        $webhookUrl = webhookGenerator('payment.gateway.mollie.webhook.dine_in', [
                            'id'       => $this->createdOrder->id,
                            'store_id' => $this->store->id,
                        ], ['res_id' => $this->storeReservation->id], SystemTypes::DINE_IN);
                    } else {
                        $redirectUrl = generalUrlGenerator('payment.gateway.mollie.redirectUrl.dine_in', [
                            'id'       => $this->createdOrder->id,
                            'store_id' => $this->store->id,
                        ], ['url' => $this->payload['url']], SystemTypes::DINE_IN);

                        $webhookUrl = webhookGenerator('payment.gateway.mollie.webhook.dine_in', [
                            'id'       => $this->createdOrder->id,
                            'store_id' => $this->store->id,
                        ], [], SystemTypes::DINE_IN);
                    }
                }

                $payload = [
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
                ];

                companionLogger('--mollie payload : ', $payload);

                $isWebhookExcluded = config('eatcardCompanion.payment.settings.exclude_webhook');
                if ($isWebhookExcluded) {
                    unset($payload['webhookUrl']);
                }

                $payment = Mollie::api()->payments()->create($payload);

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
    }

    protected function multiSafePayment()
    {
        if (in_array($this->system, [SystemTypes::TAKEAWAY, SystemTypes::DINE_IN]) && $this->createdOrder->payment_method_type == 'multisafepay') {
            if ($this->system === SystemTypes::TAKEAWAY) {
                $webhookUrl = webhookGenerator('payment.gateway.multisafe.webhook.takeaway', [
                            'id'       => $this->createdOrder->id,
                            'store_id' => $this->store->id,
                        ], [], SystemTypes::TAKEAWAY);

                $redirectUrl = generalUrlGenerator('payment.gateway.multisafe.redirectUrl.takeaway', [
                    'id'       => $this->createdOrder->id,
                    'store_id' => $this->store->id,
                ], ['url' => $this->payload['url']], SystemTypes::TAKEAWAY);

                $cancelUrl = generalUrlGenerator('payment.gateway.multisafe.cancelUrl.takeaway', [
                    'id'       => $this->createdOrder->id,
                    'store_id' => $this->store->id,
                ], ['url' => $this->payload['url']], SystemTypes::TAKEAWAY);
            }

            if ($this->system === SystemTypes::DINE_IN) {
                if (! empty($this->storeReservation)) {
                    $webhookUrl = webhookGenerator('payment.gateway.multisafe.webhook.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['res_id' => $this->storeReservation->id], SystemTypes::DINE_IN);
                    $redirectUrl = generalUrlGenerator('payment.gateway.multisafe.redirectUrl.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['url' => $this->payload['url'], 'res_id' => $this->storeReservation->id], SystemTypes::DINE_IN);
                    $cancelUrl = generalUrlGenerator('payment.gateway.multisafe.cancelUrl.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['url' => $this->payload['url'], 'res_id' => $this->storeReservation->id], SystemTypes::DINE_IN);
                } else {
                    $webhookUrl = webhookGenerator('payment.gateway.multisafe.webhook.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], [], SystemTypes::DINE_IN);
                    $redirectUrl = generalUrlGenerator('payment.gateway.multisafe.redirectUrl.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['url' => $this->payload['url']], SystemTypes::DINE_IN);
                    $cancelUrl = generalUrlGenerator('payment.gateway.multisafe.cancelUrl.dine_in', [
                        'id'       => $this->createdOrder->id,
                        'store_id' => $this->store->id,
                    ], ['url' => $this->payload['url']], SystemTypes::DINE_IN);
                }
            }

            $data = [
                'type'            => $this->createdOrder->method == 'IDEAL' ? 'direct' : 'redirect',
                'currency'        => 'EUR',
                'order_id'        => $this->createdOrder->id.'-'.$this->createdOrder->order_id,
                'amount'          => (int) ((float) number_format($this->createdOrder->total_price, 2) * 100),
                'gateway'         => $this->createdOrder->method,
                'description'     => 'Order #'.$this->createdOrder->order_id,
                'gateway_info'    => [
                    'issuer_id' => isset($this->payload['issuer_id']) && $this->payload['method'] == 'IDEAL' ? $this->payload['issuer_id'] : null,
                ],
                'payment_options' => [
                    'notification_url' => $webhookUrl,
                    'redirect_url'     => $redirectUrl,
                    'cancel_url'       => $cancelUrl,
                    'close_window'     => true,
                ],
            ];

            companionLogger('--multiSafe payload : ', $data);

            $isWebhookExcluded = config('eatcardCompanion.payment.settings.exclude_webhook');
            if ($isWebhookExcluded) {
                unset($data['payment_options']['notification_url']);
            }

            $payment = MultiSafe::setApiKey($this->store->multiSafe->api_key)->postOrder($data);
            if (isset($payment['payment_url'])) {
                $this->paymentResponse = $payment;
            } else {
                $this->paymentResponse = [
                    'multisafe_error_message' => $payment['multisafe_error_message'],
                ];
            }
        }
    }

    protected function cashPayment()
    {
        if ($this->system === SystemTypes::DINE_IN && in_array($this->orderData['method'], ['cash', 'pin'])) {
            $this->paymentResponse = [
                'store_slug' => $this->store->store_slug,
                'orderId' => $this->createdOrder->id,
                'reservationId' => $this->createdOrder->parent_id,
            ];

            //need to uto checkout guest user after his order place if setting is on.
            if (! empty($this->storeReservation) && $this->storeReservation->is_dine_in == 1) {

                //no need to add table id condition because guest use not have multiple table
                DineinCart::where('reservation_id', $this->storeReservation->id)->delete();

                $autocheckout_after_payment = isset($this->store->storeButler->autocheckout_after_payment) && $this->store->storeButler->autocheckout_after_payment ?? 0;
                if ($autocheckout_after_payment == 1) {
                    //auto checkout after payment if setting is on
                    $this->storeReservation->update([
                        'end_time'      => Carbon::now()->format('H:i'),
                        'is_checkout'   => 1,
                        'checkout_from' => 'dine_in_2',
                    ]);
                    companionLogger(' Reservation checkout after payment for Dine-in Guest user');
                }
            }
        }
    }

    public function sendPrintJsonToSQS()
    {
        // here we give print for only cash,pin,pay-later and kiosk bop payments, rest all order print will be done after payment success webhook come
        $printRes = [];

        if ($this->system === SystemTypes::DINE_IN && in_array($this->orderData['method'], ['cash', 'pin'])) {
            $printRes = EatcardPrint::generator(PaidOrderGenerator::class)
                ->method(PrintMethod::SQS)
                ->type(PrintTypes::DEFAULT)
                ->system(SystemTypes::DINE_IN)
                ->payload(['order_id' => ''.$this->createdOrder['id']])
                ->generate();
        }

        if ($this->system === SystemTypes::TAKEAWAY && ($this->orderData['method'] == 'cash' || $this->createdOrder->is_paylater_order == 1)) {
            if (($this->store->future_order_print_status == 0 || ($this->createdOrder['order_date'] == Carbon::now()->format('Y-m-d') /*&& $order_time_difference <= $this->store->future_order_print_time*/))) {
                $printRes = EatcardPrint::generator(PaidOrderGenerator::class)
                    ->method(PrintMethod::SQS)
                    ->type(PrintTypes::DEFAULT)
                    ->system(SystemTypes::TAKEAWAY)
                    ->payload(['order_id' => ''.$this->createdOrder['id']])
                    ->generate();
            } else {
                Order::query()->where('id', $this->createdOrder['id'])->update(['is_future_order_print_pending' => 1]);
            }
        }

        if ($this->system === SystemTypes::KIOSK && $this->settings['bop_kiosk']['status']) {
            $printRes = EatcardPrint::generator(PaidOrderGenerator::class)
                ->method(PrintMethod::SQS)
                ->type(PrintTypes::DEFAULT)
                ->system(SystemTypes::KIOSK)
                ->payload(['order_id' => ''.$this->createdOrder['id']])
                ->generate();
        }

        //for print json send in sqs
        if ($this->store->sqs && ! empty($printRes)) {
            config([
                'queue.connections.sqs.region' => $this->store->sqs->sqs_region,
                'queue.connections.sqs.queue'  => $this->store->sqs->sqs_queue_name,
                'queue.connections.sqs.prefix' => $this->store->sqs->sqs_url,
            ]);
            try {
                Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->store->sqs->sqs_queue_name);
            } catch (\Exception $e) {
                companionLogger('SQS queue send related Exception : ', $e);
            }
        }
    }

    protected function updateOrderReferenceIdFromPaymentGateway()
    {
    }

    protected function setBypassPaymentLogicAndOverridePaymentResponse()
    {
        if ($this->system == SystemTypes::KIOSK) {
            if ($this->settings['bop_kiosk']['status']) {
	            if (isset($this->takeawaySetting) && isset($this->takeawaySetting->print_dynamic_order_no) && (int) $this->takeawaySetting->print_dynamic_order_no > 0) {
		            $orderId = ''.substr($this->createdOrder->order_id, (-1 * ((int)$this->takeawaySetting->print_dynamic_order_no)));
	            } else {
		            $orderId = ''.substr($this->createdOrder->order_id, -2);
	            }

                $this->paymentResponse = [
                    'ssai' => 'fake_ssai',
                    'reference' => 'fake_response',
                    'payUrl' => 'https://www.google.com',
                    'order_id' => $orderId,
                    'id' => encrypt($this->createdOrder['id']),
                ];
            }
        }
    }
}
