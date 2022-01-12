<?php

namespace Weboccult\EatcardCompanion\Models;

use function Weboccult\EatcardCompanion\Helpers\getChatMsgDateTimeFormat;

class Message extends \Cmgmyr\Messenger\Models\Message
{
    protected $appends = ['date_time'];

    /**
     * @return string
     */
    public function getDateTimeAttribute(): string
    {
        return getChatMsgDateTimeFormat($this->attributes['created_at']);
    }
}
