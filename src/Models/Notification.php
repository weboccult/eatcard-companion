<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'store_id',
        'type',
        'notification',
        'additional_data',
        'read_at',
    ];
}
