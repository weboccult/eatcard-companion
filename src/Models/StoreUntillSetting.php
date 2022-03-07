<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreUntillSetting extends Model
{
    protected $fillable = [
        'store_id',
        'is_check_untill',
        'untill_host_name',
        'untill_port',
        'untill_username',
        'untill_password',
        'untill_app_name',
        'untill_app_token',
    ];

    /**
     * @return HasOne
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }
}
