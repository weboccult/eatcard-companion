<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CardHistory extends Model
{
    protected $table = 'card_history';
    protected $fillable = [
        'user_id',
        'card_id',
        'point_value',
        'per_point',
        'purchase_amount',
        'points',
        'is_redeemed',
        'is_free_point',
        'person',
    ];

    /**
     * @return HasMany
     */
    public function card(): HasMany
    {
        return $this->hasMany(Card::class, 'id', 'card_id');
    }

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
