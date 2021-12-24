<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KioskDevice extends Model
{
    /**
     * @return HasOne
     */
    public function settings(): HasOne
    {
        return $this->hasOne(StorePosSetting::class, 'pos_id');
    }
}
