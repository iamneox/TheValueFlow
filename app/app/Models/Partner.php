<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'country', 'status', 'payment_terms', 'notes',
    ];

    public function trackingDomains(): HasMany
    {
        return $this->hasMany(TrackingDomain::class);
    }

    public function trafficSources(): HasMany
    {
        return $this->hasMany(TrafficSource::class);
    }

    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
