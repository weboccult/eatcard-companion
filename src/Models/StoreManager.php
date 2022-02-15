<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreManager extends Model
{
    protected $table = 'store_managers';
    protected $fillable = [
        'store_id',
        'user_id',
    ];

    public $timestamps = false;

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    /**
     * @return HasOne
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }
}
