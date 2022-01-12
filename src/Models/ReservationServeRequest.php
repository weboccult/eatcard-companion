<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationServeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'table_id',
        'serve_request_id',
        'is_served',
    ];
}
