<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DineinPriceCategory extends Model
{
    /**
     * @return HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(DineinPrices::class, 'dinein_category_id');
    }
}
