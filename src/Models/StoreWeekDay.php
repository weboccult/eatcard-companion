<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreWeekDay extends Model
{
    protected $table = 'store_weekdays';
    protected $guarded = [];

    public function weekSlots(): HasMany
    {
        return $this->hasMany(StoreSlot::class, 'store_weekdays_id');
    }
}
