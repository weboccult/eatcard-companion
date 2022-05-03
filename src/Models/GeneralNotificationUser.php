<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralNotificationUser extends Model
{
    protected $table = 'general_notification_users';

    protected $fillable = [
        'user_id',
        'notification_id',
        'created_at',
        'read_at',
        'updated_at',
    ];

    /**
     * @return BelongsTo
     */
    public function generalNotification()
    {
        return $this->belongsTo(GeneralNotification::class, 'notification_id', 'id');
    }

    /**
     * @param $query
     *
     * @return void
     */
    public function scopeUnread($query)
    {
        $query->whereNotNull('read_at');
    }
}
