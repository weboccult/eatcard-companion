<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DineinCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'cart',
        'table_id',
    ];
}
