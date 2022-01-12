<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'reservation_id',
        'cart',
        'round',
        'total_price',
        'created_from',
        'order_status',
    ];

    /**
     * @return BelongsTo
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }
}
