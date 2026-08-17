<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'channel',
        'category',
        'status',
        'recipient',
        'subject',
        'provider',
        'message',
        'notifiable_type',
        'notifiable_id',
        'triggered_by_user_id',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
