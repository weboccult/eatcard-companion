<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Processors;

use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @author Darshit Hedpara
 */
class DineInProcessor extends BaseProcessor
{
    protected string $createdFrom = 'dine_in';

    public function __construct()
    {
        parent::__construct();
    }
}
