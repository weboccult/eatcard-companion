<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreReservation extends Model
{
    use SoftDeletes;

    protected $table = 'store_reservations';

    protected $appends = [
        'dutch_date',
        'reservation_date'
        /*, 'res_dutch_date'*/,
        'is_round_exist',
    ];


    public function getReservationDateAttribute()
    {
        return $this->getRawOriginal('res_date');
    }

    public function getResDateAttribute($value)
    {
        return getDutchDate($value);
    }
}
