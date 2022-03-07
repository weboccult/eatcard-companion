<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Requests;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Table Items Info API
 * @mixin Untill
 *
 * @author Darshit Hedpara
 */
trait GetTableItemsInfoRequest
{
    /**
     * @return Untill
     */
    public function getTableItemsInfo(): Untill
    {
        return $this->build('GetTableItemsInfo.xml')->setCredentials()->setTableNumber();
    }
}
