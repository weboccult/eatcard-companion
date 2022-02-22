<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    /**
     * @return HasOne
     */
    public function storeSetting(): HasOne
    {
        return $this->hasOne(StoreSetting::class, 'store_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function takeawaySetting(): HasOne
    {
        return $this->hasOne(TakeawaySetting::class, 'store_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function store_owner(): HasOne
    {
        return $this->hasOne(StoreOwner::class, 'store_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function multiSafe(): HasOne
    {
        return $this->hasOne(MultiSafePay::class, 'store_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function kioskDevices(): HasMany
    {
        return $this->hasMany(KioskDevice::class, 'store_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function storePosSetting(): HasMany
    {
        return $this->hasMany(StorePosSetting::class, 'store_id');
    }

    /**
     * @return HasOne
     */
    public function untillSetting(): HasOne
    {
        return $this->hasOne(StoreUntillSetting::class, 'store_id', 'id');
    }
}
