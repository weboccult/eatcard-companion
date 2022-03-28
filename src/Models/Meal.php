<?php

namespace Weboccult\EatcardCompanion\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function Weboccult\EatcardCompanion\Helpers\__companionTrans;

class Meal extends Model
{
    protected $appends = [
        'time_limit_hour',
        'user_payment_type',
    ];

    /**
     * @param $value
     *
     * @return string
     */
    public function getTimeLimitHourAttribute($value): string
    {
        $hours = intdiv($this->time_limit, 60).':'.($this->time_limit % 60);

        return $hours;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getUserPaymentTypeAttribute($value): string
    {
        $paymentType = '';
        if ($this->payment_type == 1) {
            $paymentType = __companionTrans('general.full_payment');
        } elseif ($this->payment_type == 3) {
            $paymentType = __companionTrans('general.partial_payment');
        }

        return $paymentType;
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
    public function todayReservations(): HasMany
    {
        $today = Carbon::now()->format('Y-m-d');

        return $this->hasMany(StoreReservation::class, 'meal_type')->where('res_date', $today);
    }

    /**
     * @return HasMany
     */
    public function last30DaysReservations(): HasMany
    {
        $today = Carbon::now()->format('Y-m-d');
        $prev_date = Carbon::now()->subDays(30)->format('Y-m-d');

        return $this->hasMany(StoreReservation::class, 'meal_type')->where('res_date', '<=', $today)->where('res_date', '>=', $prev_date);
    }
}
