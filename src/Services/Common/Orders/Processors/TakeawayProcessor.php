<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @author Darshit Hedpara
 */
class TakeawayProcessor extends BaseProcessor
{
    protected string $createdFrom = 'takeaway';

    public function __construct()
    {
        parent::__construct();
    }
}
