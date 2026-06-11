<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomCap extends Model
{
    protected $fillable = ['partner_id', 'offer_id', 'cap_type', 'metric', 'value'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
