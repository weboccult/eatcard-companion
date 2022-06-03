<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function paymentDetail(): MorphTo
    {
        return $this->morphTo();
    }
}
