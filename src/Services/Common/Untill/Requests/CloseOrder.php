<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Requests;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Close Order API
 * @mixin Untill
 * @author Darshit Hedpara
 */
trait CloseOrder
{

    /**
     * @param string|int $paymentId
     * @return Untill
     */
    public function setPaymentId($paymentId): Untill
    {
        $this->xmlData = $this->replacer($this->xmlData, [
            'PAYMENT_ID' => $paymentId
        ]);
        return $this;
    }

    /**
     * @return Untill
     */
    public function closeOrder(): Untill
    {
        $this->build('CloseOrder.xml')->setCredentials()->setTableNumber();
        return $this;
    }

}
