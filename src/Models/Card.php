<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    protected $table = 'cards';
    protected $fillable = [
        'customer_id',
        'card_id',
        'store_id',
        'status',
        'total_points',
        'file_id',
    ];

    /**
     * @return HasOne
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    /**
     * @return HasOne
     */
    public function customer(): HasOne
    {
        return $this->hasOne('App\User', 'id', 'customer_id');
    }

    /**
     * @return BelongsTo
     */
    public function store_owner(): BelongsTo
    {
        return $this->belongsTo(StoreOwner::class, 'store_id', 'store_id');
    }

    /**
     * @return BelongsTo
     */
    public function store_manager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class, 'store_id', 'store_id');
    }

    /**
     * @return HasMany
     */
    public function card_history(): HasMany
    {
        return $this->hasMany(CardHistory::class, 'card_id', 'id');
    }
}
