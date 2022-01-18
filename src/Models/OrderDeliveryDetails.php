<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDeliveryDetails extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'order_id',
        'approx_distance',
        'approx_trip_time',
        'approx_preparation_time',
        'approx_restaurant_pickup_time',
        'approx_driver_request_time',
        'approx_order_start_preparation_time',
        'cron_status',
    ];
}
