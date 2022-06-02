<?php

namespace Weboccult\EatcardCompanion\Services\Common\Reservations\Processors;

use Weboccult\EatcardCompanion\Services\Common\Reservations\BaseProcessor;

class KioskTicketsProcessor extends BaseProcessor
{
    protected string $createdFrom = 'kioskTickets';

    public function __construct()
    {
        parent::__construct();
    }
}
