<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Partner;
use App\Models\TrackingDomain;
use App\Models\TrackingLink;
use App\Models\TrafficSource;

class TrackingLinkService
{
    public function generate(
        Offer $offer,
        Partner $partner,
        ?TrafficSource $source = null,
        ?TrackingDomain $domain = null,
        array $subs = []
    ): TrackingLink {
        $domain = $domain ?? $offer->trackingDomain ?? $partner->trackingDomains()->where('status', 'active')->first();

        if (! $domain) {
            throw new \RuntimeException('No hay dominio de tracking activo para este partner/oferta.');
        }

        $params = array_filter([
            'o' => $offer->id,
            'p' => $partner->id,
            's' => $source?->source_id,
            'sub1' => $subs['sub1'] ?? null,
            'sub2' => $subs['sub2'] ?? null,
            'sub3' => $subs['sub3'] ?? null,
            'sub4' => $subs['sub4'] ?? null,
            'sub5' => $subs['sub5'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $url = 'https://'.$domain->domain.'/c?'.http_build_query($params);

        return TrackingLink::updateOrCreate(
            [
                'offer_id' => $offer->id,
                'partner_id' => $partner->id,
                'traffic_source_id' => $source?->id,
                'tracking_domain_id' => $domain->id,
            ],
            [
                'url' => $url,
                'aff_sub1' => $subs['sub1'] ?? null,
                'aff_sub2' => $subs['sub2'] ?? null,
                'aff_sub3' => $subs['sub3'] ?? null,
                'aff_sub4' => $subs['sub4'] ?? null,
                'aff_sub5' => $subs['sub5'] ?? null,
            ]
        );
    }

    public function buildImpressionPixelUrl(Offer $offer, Partner $partner, ?TrafficSource $source, TrackingDomain $domain): string
    {
        $params = array_filter([
            'o' => $offer->id,
            'p' => $partner->id,
            's' => $source?->source_id,
        ]);

        return 'https://'.$domain->domain.'/i?'.http_build_query($params);
    }
}
