<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration',
        'feature',
    ];
}
