<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreBillingCharges extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'app_id',
        'amount',
        'current_wallet_amount',
        'valid_for',
        'valid_from',
        'valid_to',
    ];
}
