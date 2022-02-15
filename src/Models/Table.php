<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    /**
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(ReservationTable::class, 'table_id');
    }

    /**
     * @return BelongsTo
     */
    public function diningArea(): BelongsTo
    {
        return $this->belongsTo(DiningArea::class, 'dining_area_id');
    }
}
