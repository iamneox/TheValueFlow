<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Creative extends Model
{
    protected $fillable = [
        'offer_id', 'type', 'name', 'file_path', 'html_content',
        'subject', 'sender_name', 'mandatory_mentions', 'status',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
