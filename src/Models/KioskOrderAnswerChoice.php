<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KioskOrderAnswerChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'question',
        'answer',
    ];
}
