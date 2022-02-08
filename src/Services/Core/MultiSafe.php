<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\reverseRouteGenerator;

/**
 * @author Darshit Hedpara
 */
class MultiSafe
{
    private string $apiKey;
    private string $mode;
    private string $paymentUrl;

    /**
     * Multisafe constructor.
     * load payment url as per the payment mode.
     */
    public function __construct()
    {
        $this->mode = config('eatcardCompanion.payment.gateway.multisafe.mode');
        $this->paymentUrl = $this->mode == 'live' ? config('eatcardCompanion.payment.gateway.multisafe.production') : config('eatcardCompanion.payment.gateway.multisafe.staging');
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     *
     * @return MultiSafe
     */
    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * @param string $paymentUrl
     *
     * @return MultiSafe
     */
    public function setPaymentUrl(string $paymentUrl): self
    {
        $this->paymentUrl = $paymentUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return MultiSafe
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @throws GuzzleException
     *
     * @return array|false
     * @Description getting list of payment methods of multi safe pay payment gateway
     */
    public function getPaymentMethods()
    {
        $client = new Client(['headers' => ['api_key' => $this->apiKey]]);
        $paymentMethodUrl = config('eatcardCompanion.payment.gateway.multisafe.endpoints.paymentMethod');
        $request = $client->request('GET', $this->paymentUrl.$paymentMethodUrl);
        $statusCode = $request->getStatusCode();
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        if ($statusCode == 200 && isset($response['data'])) {
            $payment_methods = [];
            foreach ($response['data'] as $payment_method) {
                $payment_method['payment_class'] = strtolower(str_replace(' ', '-', $payment_method['description']));
                $payment_methods[] = $payment_method;
            }

            return $payment_methods;
        } else {
            return false;
        }
    }

    /**
     * @throws GuzzleException
     *
     * @return mixed
     * @Description getting list of issuers of multi safe pay payment gateway
     */
    public function getIssuers()
    {
        $client = new Client(['headers' => ['api_key' => $this->apiKey]]);
        $issuerUrl = config('eatcardCompanion.payment.gateway.multisafe.endpoints.issuer');
        $request = $client->request('GET', $this->paymentUrl.$issuerUrl);
        $statusCode = $request->getStatusCode();
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        if ($statusCode == 200 && isset($response['data'])) {
            return $response['data'];
        } else {
            return false;
        }
    }

    /**
     * @param array|null $data
     *
     * @throws GuzzleException
     *
     * @return mixed
     * @Description post order and after redirect to multi safe payment screen
     */
    public function postOrder(?array $data)
    {
        $response = null;
        try {
            $client = new Client(['headers' => ['api_key' => $this->apiKey]]);
            $createOrderUrl = config('eatcardCompanion.payment.gateway.multisafe.endpoints.createOrder');
            $request = $client->request('POST', $this->paymentUrl.$createOrderUrl, [
                'form_params' => $data,
            ]);
            companionLogger('multisafe postorder info :', json_encode($response, JSON_PRETTY_PRINT));
        } catch (RequestException $e) {
            $json_response = $e->getResponse()->getBody()->getContents();
            companionLogger('Request exception in multisafe :', json_encode($response, JSON_PRETTY_PRINT));
            $response = json_decode($json_response, true);
            companionLogger('error code :', $response['error_code']);
            if (isset($response) && isset($response['error_code']) && ! empty($response['error_code'])) {
                return $this->multiSafeErrorCode($response['error_code']);
            } else {
                $error_data['multisafe_error_message'] = 'Something went wrong';

                return $error_data;
            }
        } catch (\Exception $e) {
            companionLogger('Normal exception in multisafe', 'Error : '.$e->getMessage(), 'Line : '.$e->getLine(), 'File : '.$e->getFile(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
        }
        $statusCode = $request->getStatusCode();
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        companionLogger('check after catch');
        if ($statusCode == 200 && (isset($response['success']) && $response['success']) && isset($response['data'])) {
            return $response['data'];
        } else {
            if (isset($response) && isset($response['error_code'])) {
                return $this->multiSafeErrorCode($response['error_code']);
            } else {
                $error_data['multisafe_error_message'] = 'Something went wrong';

                return $error_data;
            }
        }
    }

    /**
     * @param string $orderId
     *
     * @throws GuzzleException
     *
     * @return mixed
     * @Description get order details of particular payment using order id
     */
    public function getOrder(string $orderId)
    {
        $client = new Client(['headers' => ['api_key' => $this->apiKey]]);
        $getOrderUrl = reverseRouteGenerator('payment.gateway.multisafe.endpoints.getOrder', ['order_id' => $orderId], []);
        $request = $client->request('GET', $this->paymentUrl.$getOrderUrl);
        $statusCode = $request->getStatusCode();
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        if ($statusCode == 200 && isset($response['data'])) {
            return $response['data'];
        } else {
            return false;
        }
    }

    /**
     * @param string $orderId
     * @param array|null $data
     *
     * @throws GuzzleException
     *
     * @return mixed
     * @Description refund payment amount of particular order
     */
    public function refundOrder(string $orderId, ?array $data)
    {
        $client = new Client(['headers' => ['api_key' => $this->apiKey]]);
        $refundOrderUrl = reverseRouteGenerator('payment.gateway.multisafe.endpoints.refundOrder', ['order_id' => $orderId], []);
        $request = $client->request('POST', $this->paymentUrl.$refundOrderUrl, [
            'form_params' => $data,
        ]);
        $statusCode = $request->getStatusCode();
        $request->getHeaderLine('content-type');
        $response = json_decode($request->getBody()->getContents(), true);
        if ($statusCode == 200 && isset($response['data'])) {
            return $response['data'];
        } else {
            return false;
        }
    }

    /**
     * @param $errorCode
     *
     * @return array
     */
    protected function multiSafeErrorCode($errorCode): array
    {
        if ($errorCode == 1000) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : Transaction not allowed. Payment method is disabled or not available.';
        } elseif ($errorCode == 1001) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : An invalid amount has been received within a transaction request.';
        } elseif ($errorCode == 1006) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : The transaction ID is invalid.';
        } elseif ($errorCode == 1016) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : Amount is 0 or missing data';
        } elseif ($errorCode == 1017) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : MultiSafepay wallet does not have sufficient funds to complete the transaction';
        } elseif ($errorCode == 1019) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : Site is not active.';
        } elseif ($errorCode == 1032) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : Invalid API key';
        } elseif ($errorCode == 9999) {
            $error_data['multisafe_error_message'] = 'Something went wrong from the payment gateway | Error : Unknown error';
        } else {
            $error_data['multisafe_error_message'] = 'Something went wrong';
        }

        return $error_data;
    }
}
