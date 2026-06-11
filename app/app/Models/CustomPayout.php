<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomPayout extends Model
{
    protected $fillable = ['partner_id', 'offer_id', 'event_name', 'payout'];

    protected function casts(): array
    {
        return ['payout' => 'decimal:4'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
