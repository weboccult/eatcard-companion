<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Processors;

use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;

class PosProcessor extends BaseProcessor
{
    protected string $createdFrom = 'posTickets';

    public function __construct()
    {
        parent::__construct();
    }
}
