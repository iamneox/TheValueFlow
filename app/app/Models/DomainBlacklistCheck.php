<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainBlacklistCheck extends Model
{
    protected $fillable = ['tracking_domain_id', 'list_name', 'is_listed', 'details', 'checked_at'];

    protected function casts(): array
    {
        return [
            'is_listed' => 'boolean',
            'details' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function trackingDomain(): BelongsTo
    {
        return $this->belongsTo(TrackingDomain::class);
    }
}
