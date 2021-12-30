<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Weboccult\EatcardCompanion\Helpers\getDutchDate;

class StoreReservation extends Model
{
    use SoftDeletes;

    protected $table = 'store_reservations';

    /**
     * @return array|mixed
     */
    public function getReservationDateAttribute()
    {
        return $this->getRawOriginal('res_date');
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getResDateAttribute($value): string
    {
        return getDutchDate($value);
    }

    /**
     * @return BelongsTo
     */
    public function dineInPrice(): BelongsTo
    {
        return $this->belongsTo(DineinPrices::class, 'dinein_price_id')->with(['dineInCategory']);
    }

    /**
     * @return HasMany
     */
    public function tables(): HasMany
    {
        return $this->hasMany(ReservationTable::class, 'reservation_id');
    }

    /**
     * @return BelongsToMany
     */
    public function tables2(): BelongsToMany
    {
        return $this->belongsToMany(Table::class, 'reservation_tables', 'reservation_id', 'table_id');
    }
}
