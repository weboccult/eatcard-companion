<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Enums\TransactionTypes;
use Weboccult\EatcardCompanion\Exceptions\DeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DineInPriceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationAmountLessThenZero;
use Weboccult\EatcardCompanion\Exceptions\ReservationCancelled;
use Weboccult\EatcardCompanion\Exceptions\ReservationCheckIn;
use Weboccult\EatcardCompanion\Exceptions\ReservationExpired;
use Weboccult\EatcardCompanion\Exceptions\ReservationIsNotAyce;
use Weboccult\EatcardCompanion\Exceptions\ReservationNoShow;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\DineinPrices;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Exceptions\MealEmptyException;
use Weboccult\EatcardCompanion\Exceptions\MealIdEmptyException;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\EatcardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\CashTicketsWebhook;
use function Weboccult\EatcardCompanion\Helpers\calculateAllYouCanEatPerson;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generalUrlGenerator;
use function Weboccult\EatcardCompanion\Helpers\getAycePrice;
use function Weboccult\EatcardCompanion\Helpers\phpEncrypt;
use function Weboccult\EatcardCompanion\Helpers\webhookGenerator;

abstract class BaseReservationUpdate
{
    use MagicAccessors;
    use AttributeHelpers;

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var Meal|null|object */
    protected ?meal $meal;

    /** @var StoreReservation|null|object */
    protected ?StoreReservation $reservation;

    /** @var KioskDevice|null|object */
    protected ?KioskDevice $device;

    /** @var DineinPrices|null|object */
    protected ?DineinPrices $dineInPrice;

    protected string $system = 'none';

    public $payload;

    public $dineInPriceId;

    public $allReservation;

    public $returnResponseData;

    public $updatePayload;

    public $allYouEatPrice;

    public array $paymentDevicePayload = [];

    protected $isBOP = false;

    protected array $commonRules = [];

    protected $paymentResponse = null;

    protected float $payableAmount = 0;

    /**
     * @param mixed $payload
     *
     * @return BaseReservationUpdate
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @param string $system
     *
     * @return BaseReservationUpdate
     */
    public function setSystem(string $system): self
    {
        $this->system = $system;

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function dispatch()
    {
        try {
            $this->Stage0SetDefaultData();
            $this->Stage1PrepareValidationRules();
            $this->Stage2ValidateValidationRules();
            $this->stage3PrepareAllYouCanEatDataForUpdate();
            $this->stage4PaymentForUpdateReservation();
            $this->Stage5prepareAndSendJsonResponse();

            return $this->paymentResponse;
        } catch (\Exception $e) {
            dd($e);
            companionLogger('----Companion slots Exception', $e->getMessage(), $e->getFile(), $e->getLine());
            throw new \Exception($e->getMessage() ?? 'Something went wrong...!');
        }
    }

    protected function Stage0SetDefaultData()
    {
        $this->store = Store::query()->find($this->payload['store_id'] ?? 0);
        $this->meal = Meal::query()->find($this->payload['meal_type'] ?? 0);
        $this->reservation = StoreReservation::query()->find($this->payload['reservation_id'] ?? 0);
        $this->device = KioskDevice::query()->find($this->payload['device_id'] ?? 0);
        $this->dineInPrice = DineinPrices::withTrashed()->with([
                    'meal',
                    'dineInCategory',
                    'dynamicPrices',
                ])->where('id', $this->payload['dinein_price_id'] ?? 0)->first();

        $this->isBOP = isset($this->payload['bop']) && $this->payload['bop'] == 'wot@tickets';
    }

    protected function Stage1PrepareValidationRules()
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $reservationDate = Carbon::parse($this->reservation->reservation_date)->format('Y-m-d');

        $this->addRuleToCommonRules(StoreReservationEmptyException::class, empty($this->reservation ?? ''));
        $this->addRuleToCommonRules(MealEmptyException::class, empty($this->meal ?? ''));
        $this->addRuleToCommonRules(DeviceEmptyException::class, empty($this->device ?? ''));
        $this->addRuleToCommonRules(DineInPriceEmptyException::class, $this->dineInPrice->count() == 0);
        $this->addRuleToCommonRules(MealIdEmptyException::class, ! empty($this->reservation->is_checkout ?? 0));
        $this->addRuleToCommonRules(ReservationCancelled::class, $this->reservation->status == 'cancelled');
        $this->addRuleToCommonRules(ReservationCheckIn::class, $this->reservation->is_seated == 1);
        $this->addRuleToCommonRules(ReservationNoShow::class, $this->reservation->is_seated == 2);
        $this->addRuleToCommonRules(ReservationIsNotAyce::class, $this->reservation->reservation_type != 'all_you_eat');
        $this->addRuleToCommonRules(ReservationExpired::class, $reservationDate < $currentDate);
        // TODO : validate this reservation is valid
    }

    protected function Stage2ValidateValidationRules()
    {
        companionLogger('----Tickets Reservation Update common rule  : ', $this->getCommonRules());
        foreach ($this->getCommonRules() as $ex => $condition) {
            throw_if($condition, new $ex());
        }
    }

    protected function stage3PrepareAllYouCanEatDataForUpdate()
    {
        $reservationAllYouEatData = $this->reservation['all_you_eat_data'] ?? [];
        $newAllYouEatData = $this->payload['ayceData'] ?? [];
        $notEmptyNewAndOldAllYouEatData = empty($reservationAllYouEatData) && empty($newAllYouEatData);

        //TODO :: add exception

        $dineInPrice = $this->dineInPrice->toArray();
        if (! $notEmptyNewAndOldAllYouEatData) {
            $newAllYouEatData = json_decode($newAllYouEatData, true);
            $reservationAllYouEatData = json_decode($reservationAllYouEatData, true);

            $reservationAllYouEatData['no_of_adults'] = $newAllYouEatData['no_of_adults'] ?? 0;
            $reservationAllYouEatData['no_of_kids2'] = $newAllYouEatData['no_of_kids2'] ?? 0;
            $reservationAllYouEatData['no_of_kids'] = $newAllYouEatData['no_of_kids'] ?? 0;
            $reservationAllYouEatData['kids_age'] = $newAllYouEatData['kids_age'] ?? [];
        }

        $dynmKids = $newAllYouEatData['dynm_kids'] ?? null;
        if (! empty($dynmKids)) {
            $ayceDynamicChildeList = collect($dynmKids);
            $aycePriceClassIds = $ayceDynamicChildeList->pluck('id')->toArray();
            foreach ($dineInPrice['dynamic_prices'] as $dy_price_key => $dynamicPrices) {
                if (isset($ayceDynamicChildeList) && isset($aycePriceClassIds) && in_array($dynamicPrices['id'], $aycePriceClassIds)) {
                    $ayce_person = collect($ayceDynamicChildeList)->where('id', $dynamicPrices['id'])->first();
                    $dineInPrice['dynamic_prices'][$dy_price_key]['person'] = isset($ayce_person['person']) && ! empty($ayce_person['person']) ? (int) $ayce_person['person'] : 0;
                }
            }
        }

        if ($dineInPrice && $notEmptyNewAndOldAllYouEatData) {
            $reservationAllYouEatData['dinein_price'] = $dineInPrice;
        }

        $person = calculateAllYouCanEatPerson($reservationAllYouEatData);
        $this->allYouEatPrice = getAycePrice($reservationAllYouEatData);

        $this->updatePayload = [
            'dinein_price_id'      => $this->payload['dinein_price_id'] ?? 0,
            'all_you_eat_data'     => $reservationAllYouEatData,
            'person'               => $person ?? 0,
            'total_price'          => $this->allYouEatPrice ?? 0,
            'original_total_price' => $this->allYouEatPrice ?? 0,
        ];

        $this->payableAmount = (float) ($this->allYouEatPrice - $this->reservation->total_price);

        companionLogger('----payableAmount | TotalAmount | allYouEatPrice-----', $this->payableAmount, $this->reservation->total_price, $this->allYouEatPrice);

        if ($this->payableAmount < 0) {
            throw new ReservationAmountLessThenZero();
        }
    }

    protected function stage4PaymentForUpdateReservation()
    {
        if ($this->payableAmount < 0) {
            companionLogger('price less then zero');

            return;
        }

        $paymentMethod = 'cash';
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            if (! $this->isBOP && $this->payableAmount > 0) {
                $paymentMethod = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
            }
        }

        $this->paymentDevicePayload = [
            'transaction_type'     => TransactionTypes::CREDIT,
            'payment_method_type'  => 'cash',
            'method'               => 'cash',
            'payment_status'       => 'pending',
            'local_payment_status' => 'pending',
            'amount'               => $this->payableAmount,
            'kiosk_id'             => $this->device->id,
            'process_type'         => 'update',
            'payload'              => json_encode($this->updatePayload, true),
        ];

        if ($paymentMethod == 'ccv' && $this->system == SystemTypes::KIOSKTICKETS) {
            $this->paymentDevicePayload['method'] = $this->paymentDevicePayload['payment_method_type'] = 'ccv';
            $this->ccvPayment();
        } elseif ($paymentMethod == 'wipay' && $this->system == SystemTypes::KIOSKTICKETS) {
            $this->paymentDevicePayload['method'] = $this->paymentDevicePayload['payment_method_type'] = 'wipay';
            $this->wiPayment();
        } elseif ($paymentMethod == 'cash' && $this->system == SystemTypes::KIOSKTICKETS) {
            $this->cashPayment();
        }
    }

    protected function Stage5prepareAndSendJsonResponse()
    {
        if (empty($this->paymentResponse)) {
            companionLogger('Not supported method found.!');
            $this->paymentResponse = [];
            $this->paymentResponse['error'] = 'Not supported method found.!';
        }
    }

    private function ccvPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS])) {
            $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);

            $order_id = $this->reservation->id.'-'.$paymentDetails->id;
            companionLogger('---ccv-order-id', $order_id, $this->reservation);

//            if ($this->system == SystemTypes::POS) {
//                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.reservation', [
//                    'id'       => $order_id,
//                    'store_id' => $this->store->id,
//                ], [], SystemTypes::POS);
//                $meta_data = "{'reservation': '" . $this->reservation->id . "', paymentId : '" . $paymentDetails->id . "'}";
//                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.pos', [], [], SystemTypes::POS);
//            }

            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.kiosk-tickets.reservation', [
                    'id'       => $order_id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::KIOSKTICKETS);
                $meta_data = "{'reservation': '".$this->reservation->id."', paymentId : '".$paymentDetails->id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.kiosk-tickets', [
                    'device_id' => phpEncrypt($this->device->id),
                ], [], SystemTypes::KIOSKTICKETS);
            }

            $inputs = [
                'amount'     => number_format((float) $this->payableAmount, 2, '.', ''),
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
//            if ($this->system == SystemTypes::POS) {
//                $this->paymentResponse = [
//                    'pay_url'        => $response['payUrl'],
//                    'reservation_id' => $this->reservation->id,
//                    'payment_id'     => $paymentDetails->id,
//                ];
//            }
            if ($this->system == SystemTypes::KIOSKTICKETS) {
                $this->paymentResponse = [
                    'payUrl'         => $response['payUrl'],
                    'id'             => $this->reservation->id,
                    'reservation_id' => $this->reservation->reservation_id,
                    'payment_id'     => $paymentDetails->id,
                ];
            }
        }
    }

    private function wiPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS])) {
            $order_price = round($this->payableAmount * 100, 0);
            $order_type = 'reservation';
            $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);
            $inputs = [
                'amount'    => $order_price,
                'terminal'  => $this->device->terminal_id,
                'reference' => $this->reservation->id,
                'txid'      => $order_type.'-'.$this->reservation->id.'-'.$this->reservation->store_id.'-'.($paymentDetails->id ?? 0),
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
//                $this->setDumpDieValue(['error' => $response['errormsg']]);
                throw new \Exception($response['errormsg']);
            }
            companionLogger('Wipay payment Response', $response, 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));
            isset($response['ssai']) && $response['ssai'] ? $paymentDetails->update(['transaction_id' => $response['ssai']]) : '';
            $this->paymentResponse = [
                'pay_url'        => null,
                'id'             => $this->reservation->id,
                'reservation_id' => $this->reservation->reservation_id,
                'payment_id'     => $paymentDetails->id,
            ];
        }
    }

    private function cashPayment()
    {
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            if ($this->isBOP) {
                $this->paymentDevicePayload['transaction_receipt'] = 'fake-bop payment';

                $this->paymentResponse = [
                    'ssai'           => 'fake_ssai',
                    'reference'      => 'fake_response',
                    'payUrl'         => 'https://www.google.com',
                    'id'             => $this->reservation->id,
                    'reservation_id' => $this->reservation->reservation_id,
                ];
            } elseif ($this->payableAmount == 0) {
                $this->paymentDevicePayload['transaction_receipt'] = 'zero amount';
                $this->paymentResponse = [
                    'ssai'           => '',
                    'reference'      => '',
                    'payUrl'         => '',
                    'id'             => $this->reservation->id,
                    'reservation_id' => $this->reservation->reservation_id,
                ];
            }

            $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);
            $this->paymentResponse['payment_id'] = $paymentDetails->id;

            EatcardWebhook::action(CashTicketsWebhook::class)
                    ->setOrderType('reservation')
                    ->setReservationId($this->reservation->id)
                    ->setPaymentId($paymentDetails->id)
                    ->setStoreId($this->store->id)
                    ->payload([
                        'status' => $this->payload['bop_status'] ?? 'paid',
                    ])
                    ->dispatch();
        }
    }
}
