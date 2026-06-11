<?php

namespace App\Services;

use App\Models\DomainBlacklistCheck;
use App\Models\TrackingDomain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BlacklistService
{
    protected array $dnsblZones = [
        'Spamhaus DBL' => 'dbl.spamhaus.org',
        'SURBL' => 'multi.surbl.org',
        'URIBL' => 'multi.uribl.com',
    ];

    public function checkDomain(TrackingDomain $domain): array
    {
        $results = [];
        $isListed = false;

        foreach ($this->dnsblZones as $name => $zone) {
            $listed = $this->checkDnsbl($domain->domain, $zone);
            $results[$name] = ['listed' => $listed];

            DomainBlacklistCheck::create([
                'tracking_domain_id' => $domain->id,
                'list_name' => $name,
                'is_listed' => $listed,
                'details' => ['zone' => $zone],
                'checked_at' => now(),
            ]);

            if ($listed) {
                $isListed = true;
            }
        }

        $gsb = $this->checkGoogleSafeBrowsing($domain->domain);
        $results['Google Safe Browsing'] = $gsb;

        DomainBlacklistCheck::create([
            'tracking_domain_id' => $domain->id,
            'list_name' => 'Google Safe Browsing',
            'is_listed' => $gsb['listed'],
            'details' => $gsb,
            'checked_at' => now(),
        ]);

        if ($gsb['listed']) {
            $isListed = true;
        }

        $domain->update([
            'last_blacklist_check_at' => now(),
            'is_blacklisted' => $isListed,
            'blacklist_details' => $results,
            'status' => $isListed ? 'blacklisted' : ($domain->status === 'blacklisted' ? 'active' : $domain->status),
        ]);

        if ($isListed && config('tvf.alert_email')) {
            Mail::raw("Dominio {$domain->domain} detectado en blacklist.", function ($m) {
                $m->to(config('tvf.alert_email'))->subject('Alerta blacklist — TheValueFlow');
            });
        }

        return $results;
    }

    public function checkAllDue(): void
    {
        TrackingDomain::where('status', '!=', 'paused')
            ->get()
            ->each(function (TrackingDomain $domain) {
                $hours = $domain->check_interval_hours ?? 24;
                if (! $domain->last_blacklist_check_at || $domain->last_blacklist_check_at->lt(now()->subHours($hours))) {
                    $this->checkDomain($domain);
                }
            });
    }

    protected function checkDnsbl(string $domain, string $zone): bool
    {
        $lookup = $domain.'.'.$zone;
        $result = @dns_get_record($lookup, DNS_A);

        return ! empty($result);
    }

    protected function checkGoogleSafeBrowsing(string $domain): array
    {
        $apiKey = config('tvf.google_safe_browsing_key');

        if (! $apiKey) {
            return ['listed' => false, 'skipped' => true];
        }

        try {
            $response = Http::post(
                'https://safebrowsing.googleapis.com/v4/threatMatches:find?key='.$apiKey,
                [
                    'client' => ['clientId' => 'tvf', 'clientVersion' => '1.0'],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => [['url' => 'https://'.$domain.'/']],
                    ],
                ]
            );

            return [
                'listed' => $response->successful() && ! empty($response->json('matches')),
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::warning('GSB check failed: '.$e->getMessage());

            return ['listed' => false, 'error' => $e->getMessage()];
        }
    }
}
