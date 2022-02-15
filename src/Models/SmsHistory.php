<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return MorphTo
     */
    public function responsible(): MorphTo
    {
        return $this->morphTo();
    }
}
