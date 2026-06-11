<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    protected $fillable = [
        'click_id', 'offer_id', 'partner_id', 'transaction_id', 'conversion_id',
        'status', 'payout', 'revenue', 'method', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'payout' => 'decimal:4',
            'revenue' => 'decimal:4',
            'converted_at' => 'datetime',
        ];
    }
}
