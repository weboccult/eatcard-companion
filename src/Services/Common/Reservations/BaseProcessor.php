<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations;

use Exception;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Meal;
use Weboccult\EatcardCompanion\Models\ReservationJob;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\Table;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Traits\AttributeHelpers;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Traits\MagicAccessors;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Traits\Staggable;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage0BasicDatabaseInteraction;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage1PrepareValidationRules;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage2ValidateValidations;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage3PrepareBasicData;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage4CreateProcess;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage5PaymentProcess;
use Weboccult\EatcardCompanion\Services\Common\Reservations\Stages\Stage6PrepareResponse;

/**
 * @mixin MagicAccessors
 * @mixin AttributeHelpers
 */
abstract class BaseProcessor implements BaseProcessorContract
{
    use Staggable;
    use MagicAccessors;
    use AttributeHelpers;
    use Stage0BasicDatabaseInteraction;
    use Stage1PrepareValidationRules;
    use Stage2ValidateValidations;
    use Stage3PrepareBasicData;
    use Stage4CreateProcess;
    use Stage5PaymentProcess;
    use Stage6PrepareResponse;

    protected array $config;

    protected string $createdFrom = 'companion';

    protected array $payload = [];

    protected string $system = 'none';

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var Meal|null|object */
    protected ?Meal $meal;

    /** @var null|object */
    protected $slot = null;

    /** @var StoreReservation|null|object */
    protected $storeReservation = null;

    /** @var Table|null|object */
    protected $table = null;

    /** @var KioskDevice|null|object */
    protected $device = null;

    protected array $commonRules = [];

    protected array $systemSpecificRules = [];

    protected array $reservationData = [];
    protected array $reservationJobData = [];

    /** @var StoreReservation|null|object */
    protected $createdReservation = null;

    /** @var ReservationJob|null|object */
    protected $createdReservationJobs = null;

    protected bool $isReservationCronStop = false;

    private bool $allowNowSlot = false;

    protected $settings = [
        'additional_fee' => [
            'status' => false,
            'value' => null,
        ],
    ];

    /** @var null|array|object */
    protected $paymentResponse = null;

    /**
     * @var array
     * @description It will check and perform after effect order creation process
     */
    protected $afterEffects = [];

    protected ?array $dumpDieValue = null;

    protected bool $simulate = false;

    protected string $slotType = '';
    protected $reservationDate = '';

    protected $isBOP = false;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (! file_exists(config_path('eatcardCompanion.php'))) {
            throw new Exception('eatcardCompanion.php not found in config folder you need publish it first.!');
        }
        $this->config = config('eatcardCompanion');
    }

    /**
     * @return array|void|null
     */
    public function dispatch()
    {
        return $this->stageIt([
            fn () => $this->stage0_BasicDatabaseInteraction(),
            fn () => $this->stage1_PrepareValidationRules(),
            fn () => $this->stage2_ValidateValidations(),
            fn () => $this->stage3_PrepareBasicData(),
            fn () => $this->stage4_CreateProcess(),
            fn () => $this->stage5_PaymentProcess(),
            fn () => $this->stage6_PrepareResponse(),
        ], true);
    }

    // Document and Developer guides
    // postFix Prepare = prepare array values into protected variable in the class
    // postFix Data = fetch data from the database and set values into protected variable in the class
    private function stage0_BasicDatabaseInteraction()
    {
        $this->stageIt([
            fn () => $this->setStoreData(),
            fn () => $this->setDeviceData(),
            fn () => $this->setReservationData(),
            fn () => $this->setMealData(),
            fn () => $this->setSlotData(),
            fn () => $this->setTableIds(),
        ]);
    }

    private function stage1_PrepareValidationRules()
    {
        $this->stageIt([
            fn () => $this->overridableCommonRule(),
            fn () => $this->overridableSystemSpecificRules(),
        ]);
    }

    private function stage2_ValidateValidations()
    {
        //Note : use prepared rules and throw errors
        $this->stageIt([
            fn () => $this->validateCommonRules(),
            fn () => $this->validateSystemSpecificRules(),
            fn () => $this->validateExtraRules(),
            fn () => $this->validateSlot(),
            fn () => $this->validateTime(),
            fn () => $this->validateSlotLimits(),
        ]);
    }

    private function stage3_PrepareBasicData()
    {
        $this->stageIt([
            fn () => $this->prepareBasicData(),
            fn () => $this->prepareAllYouCanEatData(),
            fn () => $this->preparePaymentData(),

        ]);
    }

    private function stage4_CreateProcess()
    {
        $this->stageIt([
            fn () => $this->isSimulateEnabled(),
            fn () => $this->createReservation(),
            fn () => $this->tableAvailabilityCheck(),
            fn () => $this->createReservationJob(),
            fn () => $this->assignTableIfCronStop(),
            fn () => $this->checkReservationJobForAssignTableStatus(),
            fn () => $this->checkReservationForAssignTableStatus(),
            fn () => $this->createChatThread(),
            fn () => $this->assignedTables(),
        ]);
    }

    private function stage5_PaymentProcess()
    {
        $this->stageIt([
            fn () => $this->ccvPayment(),
            fn () => $this->wiPayment(),
            fn () => $this->cashPayment(),
        ]);
    }

    private function stage6_PrepareResponse()
    {
        if ($this->system == SystemTypes::POS) {
            $this->posResponse();
        }
        if ($this->system == SystemTypes::KIOSKTICKETS) {
            $this->kioskTicketsResponse();
        }

        // anything you want
       // $this->setDumpDieValue([
       //     'order' => $this->orderData,
       //     'order_items' => $this->orderItemsData,
       // ]);
    }
}
