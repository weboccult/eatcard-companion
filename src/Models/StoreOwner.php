<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StoreOwner extends Model
{
    protected $table = 'store_owners';
    protected $fillable = [
        'store_id',
        'user_id',
    ];

    public $timestamps = false;

    /**
     * @return HasOne
     */
    public function user()
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

    /**
     * @return HasMany
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'store_id', 'store_id');
    }

    /**
     * @return HasOne
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(UserWallet::class, 'user_id', 'user_id');
    }
}
