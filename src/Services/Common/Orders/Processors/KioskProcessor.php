<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @author Darshit Hedpara
 */
class KioskProcessor extends BaseProcessor
{
    protected string $createdFrom = 'kiosk';

    public function __construct()
    {
        parent::__construct();
    }
}
