<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Enums\TransactionTypes;
use Weboccult\EatcardCompanion\Exceptions\AYCEDataEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\DineInPriceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\ReservationAlreadyExists;
use Weboccult\EatcardCompanion\Exceptions\ReservationAmountLessThenZero;
use Weboccult\EatcardCompanion\Exceptions\ReservationCancelled;
use Weboccult\EatcardCompanion\Exceptions\ReservationCheckIn;
use Weboccult\EatcardCompanion\Exceptions\ReservationExpired;
use Weboccult\EatcardCompanion\Exceptions\ReservationIsNotAyce;
use Weboccult\EatcardCompanion\Exceptions\ReservationNoShow;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Models\DineinPrices;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\ReservationJob;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Exceptions\MealEmptyException;
use Weboccult\EatcardCompanion\Exceptions\MealIdEmptyException;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Rectifiers\ReservationTicketsUpdate\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\EatcardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets\CashTicketsWebhook;
use function PHPUnit\Framework\throwException;
use function Weboccult\EatcardCompanion\Helpers\calculateAllYouCanEatPerson;
use function Weboccult\EatcardCompanion\Helpers\checkAnotherMeeting;
use function Weboccult\EatcardCompanion\Helpers\checkTableMinMaxLimitAccordingToPerson;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;
use function Weboccult\EatcardCompanion\Helpers\generalUrlGenerator;
use function Weboccult\EatcardCompanion\Helpers\getAycePrice;
use function Weboccult\EatcardCompanion\Helpers\phpEncrypt;
use function Weboccult\EatcardCompanion\Helpers\assignedReservationTableOrUpdate;
use function Weboccult\EatcardCompanion\Helpers\sendResWebNotification;
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

    /** @var ReservationJob|null|object */
    protected $createdReservationJobs = null;

    protected string $system = 'none';

    public $payload;

    public $dineInPriceId;

    public $allReservation;

    public $updatePayload;

    public $allYouEatPrice;

    public array $paymentDevicePayload = [];

    protected $isBOP = false;

    protected array $commonRules = [];

    protected $paymentResponse = null;

    protected $reservationJobData;

    protected bool $isReservationCronStop = false;

    protected float $payableAmount = 0;

    protected string $updatedFrom = '';

    protected bool $isReAssignTable = false;

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
            $this->stage4UpdateReservation();
            $this->stage5checkReservationJobForAssignTableStatus();
            $this->stage6checkReservationForAssignTableStatus();
            $this->stage7PaymentForUpdateReservation();
            $this->Stage8prepareAndSendJsonResponse();

            return $this->paymentResponse;
        } catch (\Exception $e) {
            companionLogger('----Companion slots Exception', $e->getMessage(), $e->getFile(), $e->getLine());
            throw new \Exception($e->getMessage() ?? 'Something went wrong...!');
        }
    }

    protected function Stage0SetDefaultData()
    {
        $this->store = Store::query()->find($this->payload['store_id'] ?? 0);
        $this->meal = Meal::query()->find($this->payload['meal_type'] ?? 0);
        $this->reservation = StoreReservation::query()->find($this->payload['reservation_id'] ?? 0);
        if (empty($this->reservation)) {
            throw new StoreReservationEmptyException();
        }

        if ($this->system == SystemTypes::POS) {
            $this->device = KioskDevice::query()
                ->where('store_id', ($this->payload['store_id'] ?? 0))
                ->where('pos_code', ($this->payload['pos_code'] ?? 0))
                ->first();
        } elseif ($this->system == SystemTypes::KIOSKTICKETS) {
            $this->device = KioskDevice::query()->find($this->payload['device_id'] ?? 0);
        }

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
        $this->addRuleToCommonRules(ReservationNoShow::class, $this->reservation->is_seated == 2);
        $this->addRuleToCommonRules(ReservationIsNotAyce::class, $this->reservation->reservation_type != 'all_you_eat');
        $this->addRuleToCommonRules(ReservationExpired::class, $reservationDate < $currentDate);

        if ($this->system == SystemTypes::POS) {
            /*<--- check for another meeting ---->*/
            $tables = $this->payload['table_ids'] ?? [];
            if (! empty($tables) && ! empty($this->meal)) {
                $this->addRuleToCommonRules(ReservationAlreadyExists::class, checkAnotherMeeting($tables, $this->reservation, $this->meal));
            }
        }
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            $this->addRuleToCommonRules(ReservationCheckIn::class, $this->reservation->is_seated == 1);

            /*<--- check for another meeting ---->*/
            $tables = $this->reservation->tables2->pluck('id')->toArray() ?? [];
            if (! empty($tables) && ! empty($this->meal)) {
                $this->addRuleToCommonRules(ReservationAlreadyExists::class, checkAnotherMeeting($tables, $this->reservation, $this->meal));
            }
        }
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
        $reservationAllYouEatData = $this->reservation['all_you_eat_data'] ?? '';
        $newAllYouEatData = $this->payload['ayceData'] ?? '';
        $isEmptyNewAndOldAllYouEatData = empty($reservationAllYouEatData) || empty($newAllYouEatData);

        if ($isEmptyNewAndOldAllYouEatData) {
            throw new AYCEDataEmptyException();
        }

        $dineInPrice = $this->dineInPrice->toArray();

        $newAllYouEatData = json_decode($newAllYouEatData, true);
        $reservationAllYouEatData = json_decode($reservationAllYouEatData, true);

        $reservationAllYouEatData['no_of_adults'] = $newAllYouEatData['no_of_adults'] ?? 0;
        $reservationAllYouEatData['no_of_kids2'] = $newAllYouEatData['no_of_kids2'] ?? 0;
        $reservationAllYouEatData['no_of_kids'] = $newAllYouEatData['no_of_kids'] ?? 0;
        $reservationAllYouEatData['kids_age'] = $newAllYouEatData['kids_age'] ?? [];

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

        if ($dineInPrice) {
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
//            throw new ReservationAmountLessThenZero();
        }

        $this->isReAssignTable = checkTableMinMaxLimitAccordingToPerson($this->reservation, $this->payload);
    }

    protected function stage4UpdateReservation()
    {
        if ($this->system == SystemTypes::KIOSKTICKETS && $this->isReAssignTable) {
            $reservationData = $this->reservation->toArray();

            $reservationData['data_model'] = $reservationData['slot_model'];
            $reservationData['store_slug'] = $this->store->store_slug ?? '';
            $reservationData['person'] = $this->updatePayload['person'] ?? $this->reservation['person'];
            $reservationData['created_from'] = $this->updatedFrom;

            $this->reservationJobData['store_id'] = $this->store->id;
            $this->reservationJobData['reservation_id'] = $this->reservation->id;
            $this->reservationJobData['attempt'] = 0;
            $this->reservationJobData['reservation_front_data'] = (json_encode($reservationData, true));

            /*create reservation entry on reservation jobs table*/
            $this->isReservationCronStop = false;
            $first_reservation = ReservationJob::query()
                ->where('attempt', 0)
                ->where('in_queue', 0)
                ->where('is_completed', 0)
                ->where('is_failed', 0)
                ->first();

            if (! empty($first_reservation)) {
                $first_reservation = ReservationJob::query()
                    ->where('attempt', 2)
                    ->where('in_queue', 0)
                    ->where('is_completed', 0)
                    ->where('is_failed', 1)
                    ->first();
            }

            $time_difference = 0;
            if (isset($first_reservation->created_at)) {
                $current_time = Carbon::now();
                $end_time = Carbon::parse($first_reservation->created_at);
                $time_difference = $current_time->diffInSeconds($end_time);
            }

            if ($time_difference > 90) {
                ReservationJob::query()->whereNotNull('id')->update([
                    'is_failed' => 1,
                    'attempt'   => 2,
                ]);
                $this->isReservationCronStop = true;
                companionLogger('reservation through normal functionality : ', (['reservation_job_first_res' => $first_reservation]));
            }

            /*<---- for testing manually cron stop using this variable ---->*/
            if (env('FORCE_STOP_CREATE_RESERVATION_USING_CRON', false)) {
                $this->isReservationCronStop = true;
                companionLogger('Manually cron skip for testing if you want to stop this remove env FORCE_STOP_CREAT_RESERVATION_USING_CRON variable or make it FALSE');
            }
            $this->createdReservationJobs = ReservationJob::query()->create($this->reservationJobData);
            $this->createdReservationJobs->refresh();
            companionLogger('----job entry create', $this->createdReservationJobs);
        }

        if ($this->system == SystemTypes::POS) {
            if (! empty($this->payload['table_ids'] ?? [])) {
                try {
                    $tables = $this->payload['table_ids'] ?? [];
                    assignedReservationTableOrUpdate($this->reservation, $tables);
                } catch (\Exception $exception) {
                    throwException($exception);
                }
            }
        }
    }

    protected function stage5checkReservationJobForAssignTableStatus()
    {
        if ($this->system == SystemTypes::POS || ! $this->isReAssignTable) {
            return;
        }

        if ($this->isReservationCronStop) {
            return;
        }

        if (empty($this->createdReservationJobs)) {
            return;
        }

        /*get reservation status*/
        $check_res_status_array = [1, 2, 3, 4, 5];
        for ($i = 0; $i < 5; $i++) {
            $reservation_jobs_count = ReservationJob::query()->where('id', $this->createdReservationJobs->id)->count();
            if ($reservation_jobs_count > 0) {
                sleep($check_res_status_array[$i]);
            } else {
                break;
            }
        }
    }

    protected function stage6checkReservationForAssignTableStatus()
    {
        if ($this->system == SystemTypes::POS || ! $this->isReAssignTable) {
            return;
        }

        $ayceData = [];
        $count = 1;
        do {
            companionLogger('do while start');

            $storeReservation = StoreReservation::query()->where('id', $this->reservation->id)->first();
            $ayceData = json_decode($storeReservation->all_you_eat_data, true);
            $reservationStatus = $ayceData['assignTableStatus'] ?? '';

            if (! empty($reservationStatus)) {
                $count = 4;
            } else {
                sleep(3);
                $count++;
            }
        } while (empty($reservationStatus) && $count <= 3);

        companionLogger('do while end');

        $this->reservation->refresh();
        if (! empty($ayceData)) {
            $reservationStatus = $ayceData['assignTableStatus'] ?? '';
            if (! empty($reservationStatus) && $reservationStatus == 'failed') {
                unset($ayceData['assignTableStatus']);
                $ayceData = json_encode($ayceData);
                StoreReservation::where('id', $this->reservation->id)->update(['all_you_eat_data' => $ayceData]);
                throw new \Exception('Sorry selected slot is not available.Please try another time slot');
            }
        }

        //if reservation table assigned successfully then send notification
        sendResWebNotification($this->reservation->id, $this->reservation->store_id);
    }

    protected function stage7PaymentForUpdateReservation()
    {
        if ($this->payableAmount < 0) {
            companionLogger('price less then zero');

//            return;
        }

        $method = $this->payload['method'] ?? '';
        $paymentMethodType = '';
        $manualPin = $this->payload['manual_pin'] ?? '';

        if ($this->isBOP) {
            $method = 'cash';
        } elseif (! empty($manualPin)) {
            $method = 'manual_pin';
        } elseif ($method == 'cash') {
            $method = 'cash';
        }

        if (! $this->isBOP && $this->payableAmount > 0 && $method != 'cash') {
            $paymentMethodType = $method = $this->device->payment_type == 'ccv' ? 'ccv' : 'wipay';
        } elseif ($this->payableAmount == 0) {
            /*<-- in kiosk device handle ZERO payment for update reservation-->*/
            $method = 'cash';
        }

        $this->paymentDevicePayload = [
            'store_id'             => $this->store->id,
            'transaction_type'     => TransactionTypes::CREDIT,
            'payment_method_type'  => $paymentMethodType,
            'method'               => $method,
            'payment_status'       => 'pending',
            'local_payment_status' => 'pending',
            'amount'               => $this->payableAmount,
            'kiosk_id'             => $this->device->id,
            'process_type'         => 'update',
            'payload'              => json_encode($this->updatePayload, true),
            'created_from'         => $this->updatedFrom,
        ];

        if ($paymentMethodType == 'ccv') {
            $this->ccvPayment();
        } elseif ($paymentMethodType == 'wipay') {
            $this->wiPayment();
        } elseif ($paymentMethodType == '' && $method == 'cash') {
            $this->cashPayment();
        }
    }

    protected function Stage8prepareAndSendJsonResponse()
    {
        if (empty($this->paymentResponse)) {
            companionLogger('Not supported method found.!');
            $this->paymentResponse = [];
            $this->paymentResponse['error'] = 'Not supported method found.!';
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function ccvPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS])) {
            $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);

            $this->reservation->update(['ref_payment_id' => $paymentDetails->id]);
            $this->reservation->refresh();

            $order_id = $this->reservation->id.'-'.$paymentDetails->id;
            companionLogger('---ccv-order-id', $order_id, $this->reservation);

            if ($this->system == SystemTypes::POS) {
                $webhook_url = webhookGenerator('payment.gateway.ccv.webhook.pos.reservation', [
                    'id'       => $order_id,
                    'store_id' => $this->store->id,
                ], [], SystemTypes::POS);
                $meta_data = "{'reservation': '".$this->reservation->id."', paymentId : '".$paymentDetails->id."'}";
                $returnUrl = generalUrlGenerator('payment.gateway.ccv.returnUrl.pos', [], [], SystemTypes::POS);
            }

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
            try {
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

                $this->paymentResponse = [
                    'payUrl'         => $response['payUrl'],
                    'id'             => $this->reservation->id,
                    'reservation_id' => $this->reservation->reservation_id,
                    'payment_id'     => $paymentDetails->id,
                ];
            } catch (ConnectException | RequestException | ClientException $e) {
                companionLogger('---------ccv exception ', $e->getMessage(), $e->getLine(), $e->getFile());

                /*<--- manual cancel payment ---->*/
                EatcardWebhook::action(CashTicketsWebhook::class)
                    ->setOrderType('reservation')
                    ->setReservationId($this->reservation->id)
                    ->setPaymentId($paymentDetails->id)
                    ->setStoreId($this->store->id)
                    ->payload(['status' => 'failed'])
                    ->dispatch();

                throw new \Exception('Currently, the payment device has not been found or unable to connect.');
            }
        }
    }

    private function wiPayment()
    {
        if (in_array($this->system, [SystemTypes::POS, SystemTypes::KIOSKTICKETS])) {
            $order_price = round($this->payableAmount * 100, 0);
            $order_type = 'reservation';
            $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);

            $this->reservation->update(['ref_payment_id' => $paymentDetails->id]);
            $this->reservation->refresh();

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

                /*<--- manual cancel payment ---->*/
                EatcardWebhook::action(CashTicketsWebhook::class)
                    ->setOrderType('reservation')
                    ->setReservationId($this->reservation->id)
                    ->setPaymentId($paymentDetails->id)
                    ->setStoreId($this->store->id)
                    ->payload(['status' => 'failed'])
                    ->dispatch();

                throw new \Exception('Currently, the payment device has not been found or unable to connect.');
            }
            companionLogger('Wipay payment Response', $response, 'IP address - '.request()->ip(), 'Browser - '.request()->header('User-Agent'));
            isset($response['ssai']) && $response['ssai'] ? $paymentDetails->update(['transaction_id' => $response['ssai']]) : '';
            $this->paymentResponse = [
                'ssai'           => $response['ssai'] ?? '',
                'payUrl'        => null,
                'id'             => $this->reservation->id,
                'reservation_id' => $this->reservation->reservation_id,
                'payment_id'     => $paymentDetails->id,
            ];
        }
    }

    private function cashPayment()
    {
        companionLogger('-------------------cash payment-1', $this->reservation);
        if (! ($this->isBOP || in_array($this->reservation->method, ['manual_pin', 'cash']))) {
            return;
        }

        $isCashPaid = $this->system == SystemTypes::POS && ! empty($this->payload['cash_paid'] ?? 0);
        if ($isCashPaid) {
            $this->paymentDevicePayload['cash_paid'] = $this->payload['cash_paid'] ?? null;
        }

        $this->paymentResponse = [
            'ssai'           => $this->isBOP ? 'fake_ssai' : '',
            'reference'      => $this->isBOP ? 'fake_response' : '',
            'payUrl'         => $this->isBOP ? 'https://www.google.com' : '',
            'id'             => $this->reservation->id,
            'reservation_id' => $this->reservation->reservation_id,
        ];

        if ($this->isBOP) {
            $this->paymentDevicePayload['transaction_receipt'] = 'fake-bop payment';
        } elseif ($this->payableAmount == 0) {
            $this->paymentDevicePayload['transaction_receipt'] = 'zero amount';
        }
        companionLogger('-------------------cash payment-2', $this->reservation);
        $paymentDetails = $this->reservation->paymentTable()->create($this->paymentDevicePayload);
        $paymentDetails->refresh();
        $this->paymentResponse['payment_id'] = $paymentDetails->id;

        $this->reservation->update(['ref_payment_id' => $paymentDetails->id]);
        companionLogger('-------------------cash payment-3', $this->reservation);
        $this->reservation->refresh();
        companionLogger('-------------------cash payment-4', $this->reservation);

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
