<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationRevenueView extends Model
{
    use HasFactory;

    public $table = 'reservation_revenue_views';

    protected $guarded = [];
}
