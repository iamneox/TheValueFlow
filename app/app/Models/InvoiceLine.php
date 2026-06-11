<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id', 'offer_id', 'campaign_name', 'quantity', 'quantity_type',
        'payout', 'total_payout', 'is_manual',
    ];

    protected function casts(): array
    {
        return [
            'payout' => 'decimal:4',
            'total_payout' => 'decimal:4',
            'is_manual' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
