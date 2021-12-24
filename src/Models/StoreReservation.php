<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
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
}
