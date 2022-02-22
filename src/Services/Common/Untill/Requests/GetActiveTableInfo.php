<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Requests;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Table Info API
 * @mixin Untill
 * @author Darshit Hedpara
 */
trait GetActiveTableInfo
{

    /**
     * @return Untill
     */
    public function getActiveTableInfo(): Untill
    {
        return $this->build('GetActiveTableInfo.xml')->setCredentials()->setTableNumber();
    }

}
