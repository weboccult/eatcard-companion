<?php

namespace Weboccult\EatcardCompanion\Services\Common\Revenue\Stages;

use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 4
 * @mixin BaseGenerator
 */
trait Stage4BasicDatabaseInteraction
{
    /**
     * @return void
     */
    protected function setStoreData()
    {
        if (empty($this->storeId)) {
            throw new StoreEmptyException();
        }

        $store = Store::with('storeSetting', 'kioskDevices', 'storePosSetting')->where('id', $this->storeId)->first();

        if (empty($store)) {
            throw new StoreEmptyException();
        }
        companionLogger('--Eatcard companion store details : ', $store);

        $this->store = $store;
    }
}
