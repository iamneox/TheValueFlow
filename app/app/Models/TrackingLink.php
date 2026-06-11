<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingLink extends Model
{
    protected $fillable = [
        'offer_id', 'partner_id', 'traffic_source_id', 'tracking_domain_id',
        'url', 'aff_sub1', 'aff_sub2', 'aff_sub3', 'aff_sub4', 'aff_sub5',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function trafficSource(): BelongsTo
    {
        return $this->belongsTo(TrafficSource::class);
    }

    public function trackingDomain(): BelongsTo
    {
        return $this->belongsTo(TrackingDomain::class);
    }
}
