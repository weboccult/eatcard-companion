<?php

namespace Weboccult\EatcardCompanion\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralNotification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'notification',
        'additional_data',
    ];

    protected $appends = ['human_readable_datetime'];

    /**
     * @return HasMany
     */
    public function generalNotificationUsers(): HasMany
    {
        return $this->hasMany(GeneralNotificationUser::class, 'notification_id', 'id');
    }

    /**
     * @return string
     */
    public function getHumanReadableDatetimeAttribute(): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->diffForHumans();
    }
}
