<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;

class MailJob extends Model
{
    protected $fillable = [
        'from_name',
        'bcc',
        'cc',
        'recipients',
        'subject',
        'mail_type',
        'message',
        'is_sent',
        'in_queue',
        'is_error',
        'entity_type',
        'entity_id',
    ];
}
