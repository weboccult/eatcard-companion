<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @author Darshit Hedpara
 */
class PosProcessor extends BaseProcessor
{
    protected string $createdFrom = 'pos';

    public function __construct()
    {
        parent::__construct();
    }
}
