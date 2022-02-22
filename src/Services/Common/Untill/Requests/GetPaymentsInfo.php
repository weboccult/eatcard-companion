<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Requests;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Table Info API
 * @mixin Untill
 * @author Darshit Hedpara
 */
trait GetPaymentsInfo
{

    /**
     * @return Untill
     */
    public function getPaymentsInfo(): Untill
    {
        return $this->build('GetPaymentsInfo.xml')->setCredentials();
    }

}
