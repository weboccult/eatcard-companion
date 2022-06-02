<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreSlot extends Model
{
    use SoftDeletes;

    protected $table = 'store_slots';
    protected $guarded = [];

    public function store_weekday(): HasOne
    {
        return $this->hasOne(StoreWeekDay::class, 'id', 'store_weekdays_id');
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function meal(): HasOne
    {
        return $this->hasOne(Meal::class, 'id', 'meal_id');
    }
}
