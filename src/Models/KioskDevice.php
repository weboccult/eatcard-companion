<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;

class KioskDevice extends Model
{
    public function settings()
    {
        return $this->hasOne(StorePosSetting::class, 'pos_id');
    }
}
