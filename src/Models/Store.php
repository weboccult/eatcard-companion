<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    public function storeSetting()
    {
        return $this->hasOne(StoreSetting::class, 'store_id', 'id');
    }
}
