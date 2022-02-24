<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'store_id',
        'is_res_booking_email',
        'is_res_booking_notification',
        'is_takeaway_email',
        'is_takeaway_notification',
        'is_dine_in_email',
        'is_dine_in_notification',
        'is_owner_register_email',
        'is_cancel_payment_email',
    ];
}
