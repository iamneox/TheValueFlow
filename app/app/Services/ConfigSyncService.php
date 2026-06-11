<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Partner;
use App\Models\TrackingDomain;
use Illuminate\Support\Facades\Redis;

class ConfigSyncService
{
    public function syncAll(): void
    {
        $this->syncOffers();
        $this->syncPartners();
        $this->syncDomains();
    }

    public function syncOffer(Offer $offer): void
    {
        $landing = $offer->landingPages()->where('is_default', true)->first()
            ?? $offer->landingPages()->where('is_active', true)->first();

        $payload = json_encode([
            'id' => $offer->id,
            'slug' => $offer->slug,
            'status' => $offer->status,
            'type' => $offer->type,
            'payout' => (float) $offer->payout,
            'revenue' => (float) $offer->revenue,
            'allowed_countries' => $offer->allowed_countries,
            'allowed_days' => $offer->allowed_days,
            'allowed_hours_start' => $offer->allowed_hours_start,
            'allowed_hours_end' => $offer->allowed_hours_end,
            'daily_cap' => $offer->daily_cap,
            'monthly_cap' => $offer->monthly_cap,
            'cap_type' => $offer->cap_type,
            'landing_url' => $landing?->url,
            'tracking_domain_id' => $offer->tracking_domain_id,
        ]);

        Redis::set('offer:'.$offer->id, $payload);
        Redis::connection('tracker')->set('offer:'.$offer->id, $payload);
    }

    public function syncPartner(Partner $partner): void
    {
        $payload = json_encode([
            'id' => $partner->id,
            'status' => $partner->status,
        ]);

        Redis::set('partner:'.$partner->id, $payload);
        Redis::connection('tracker')->set('partner:'.$partner->id, $payload);
    }

    public function syncDomain(TrackingDomain $domain): void
    {
        $payload = json_encode([
            'id' => $domain->id,
            'domain' => $domain->domain,
            'partner_id' => $domain->partner_id,
            'status' => $domain->status,
            'is_blacklisted' => $domain->is_blacklisted,
        ]);

        Redis::set('domain:'.$domain->domain, $payload);
        Redis::connection('tracker')->set('domain:'.$domain->domain, $payload);
    }

    protected function syncOffers(): void
    {
        Offer::all()->each(fn ($o) => $this->syncOffer($o));
    }

    protected function syncPartners(): void
    {
        Partner::all()->each(fn ($p) => $this->syncPartner($p));
    }

    protected function syncDomains(): void
    {
        TrackingDomain::all()->each(fn ($d) => $this->syncDomain($d));
    }
}
