<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'status',
        'from_zip_code',
        'to_zip_code',
        'delivery_fee',
        'is_delivery_min_amount',
        'delivery_free_amount',
    ];
}
