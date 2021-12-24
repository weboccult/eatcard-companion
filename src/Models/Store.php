<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
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
}
