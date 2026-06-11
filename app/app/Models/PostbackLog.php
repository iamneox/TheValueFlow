<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostbackLog extends Model
{
    protected $fillable = [
        'postback_id', 'conversion_id', 'url', 'http_status', 'response_body', 'attempt', 'status',
    ];

    public function postback(): BelongsTo
    {
        return $this->belongsTo(Postback::class);
    }
}
