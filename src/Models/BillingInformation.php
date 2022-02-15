<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address',
        'auto_recharge_enable',
        'auto_recharge_amount',
        'auto_recharge_threshold',
    ];

    protected $appends = [
        'user_recharge_amount',
        'user_recharge_threshold',
    ];

    /**
     * @return string
     */
    public function getUserRechargeAmountAttribute(): string
    {
        return number_format((float) $this->auto_recharge_amount, 2, ',', '');
    }

    /**
     * @return string
     */
    public function getUserRechargeThresholdAttribute(): string
    {
        return number_format((float) $this->auto_recharge_threshold, 2, ',', '');
    }
}
