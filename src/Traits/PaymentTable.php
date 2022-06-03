<?php

namespace Weboccult\EatcardCompanion\Traits;

use Weboccult\EatcardCompanion\Models\PaymentDetail;

trait PaymentTable
{
    /**
     * @return mixed
     */
    public function paymentTable()
    {
        return $this->morphMany(PaymentDetail::class, 'paymentable');
    }
}
