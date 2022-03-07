<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Illuminate\Support\Facades\Session;
use Throwable;
use Weboccult\EatcardCompanion\Exceptions\KioskDeviceEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\StoreReservationEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayDateNotAvailableException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderDateTimeNotValidException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayOrderTypeMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodMisMatchedException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPaymentMethodNotFoundException;
use Weboccult\EatcardCompanion\Exceptions\TakeawayPickupDeliveryNotAvailableException;
use Weboccult\EatcardCompanion\Exceptions\TakeawaySettingNotFoundException;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;

/**
 * @description Stag 2
 *
 * @author Darshit Hedpara
 */
trait Stage2ValidateValidations
{
    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateCommonRules()
    {
        try {
            foreach ($this->getCommonRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (StoreEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store not found']);
        }
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function validateSystemSpecificRules()
    {
        try {
            foreach ($this->getSystemSpecificRules() as $ex => $condition) {
                throw_if($condition, new $ex());
            }
        } catch (KioskDeviceEmptyException $e) {
            $this->setDumpDieValue(['error' => 'device not found']);
        } catch (StoreReservationEmptyException $e) {
            $this->setDumpDieValue(['error' => 'store reservation not found']);
        } catch (TakeawayOrderTypeEmptyException | TakeawayOrderTypeMisMatchedException $e) {
            $this->setDumpDieValue(['not_available_order_type' => 'error']);
        } catch (TakeawayOrderDateTimeEmptyException | TakeawayOrderDateTimeNotValidException $e) {
            $this->setDumpDieValue(['date_not_valid' => 'error']);
        } catch (TakeawayPaymentMethodMisMatchedException $e) {
            $this->setDumpDieValue(['payment_type_not_valid' => 'error']);
        } catch (TakeawaySettingNotFoundException $e) {
            $this->setDumpDieValue(['setting_not_found' => 'error']);
        } catch (TakeawayPaymentMethodNotFoundException $e) {
            $this->setDumpDieValue(['payment_method_not_found' => 'error']);
        } catch (TakeawayPickupDeliveryNotAvailableException $e) {
            Session::flash('error', __companionTrans('takeaway.pickup_delivery_not_available'));
            $this->setDumpDieValue(['error' => 'error']);
        } catch (TakeawayDateNotAvailableException $e) {
            $this->setDumpDieValue(['date_not_available' => 'error']);
        }
    }

    protected function validateExtraRules()
    {
    }
}
