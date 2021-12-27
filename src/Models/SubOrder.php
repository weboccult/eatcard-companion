<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubOrder extends Model
{
    /**
     * @return BelongsTo
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function subOrderItems(): HasMany
    {
        return $this->hasMany(SubOrderItem::class, 'sub_order_id');
    }
}
