<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adjustment extends Model
{
    protected $fillable = [
        'offer_id', 'partner_id', 'metric', 'value', 'transaction_id', 'method', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return ['value' => 'decimal:4'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AdjustmentHistory::class);
    }
}
