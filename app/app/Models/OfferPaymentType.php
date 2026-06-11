<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPaymentType extends Model
{
    protected $fillable = ['offer_id', 'type', 'revenue', 'payout'];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:4',
            'payout' => 'decimal:4',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
