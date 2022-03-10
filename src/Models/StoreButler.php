<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreButler extends Model
{

	public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }
}
