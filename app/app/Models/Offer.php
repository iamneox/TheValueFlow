<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    protected $fillable = [
        'name', 'slug', 'client_id', 'type', 'category', 'country', 'revenue', 'payout',
        'from_name_advertiser', 'status', 'allowed_countries', 'allowed_days',
        'allowed_hours_start', 'allowed_hours_end', 'daily_cap', 'monthly_cap', 'cap_type',
        'start_date', 'end_date', 'tracking_domain_id',
    ];

    protected function casts(): array
    {
        return [
            'allowed_countries' => 'array',
            'allowed_days' => 'array',
            'revenue' => 'decimal:4',
            'payout' => 'decimal:4',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function trackingDomain(): BelongsTo
    {
        return $this->belongsTo(TrackingDomain::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(OfferLandingPage::class);
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(Creative::class);
    }

    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }

    public function partnerAccess(): HasMany
    {
        return $this->hasMany(OfferPartnerAccess::class);
    }
}
