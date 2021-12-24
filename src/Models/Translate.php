<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translate extends Model
{
    public $timestamps = true;

    /**
     * @return MorphTo
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
