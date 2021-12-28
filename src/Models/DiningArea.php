<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiningArea extends Model
{
    /**
     * @return HasMany
     */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class, 'dining_area_id');
    }

    /**
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
