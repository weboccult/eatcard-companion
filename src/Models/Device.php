<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'onesignal_id',
        'device_name',
        'platform',
    ];
    protected $dates = ['deleted_at'];
}
