<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;

class MultiSafePay extends Model
{
    protected $fillable = [
        'store_id',
        'is_check_multisafe',
        'api_key',
        'MAESTRO',
        'BANKTRANS',
        'ALIPAY',
        'DIRECTBANK',
        'GIROPAY',
        'MISTERCASH',
        'EPS',
        'IDEAL',
        'TRUSTLY',
        'MASTERCARD',
        'APPLEPAY',
        'VISA',
    ];
}
