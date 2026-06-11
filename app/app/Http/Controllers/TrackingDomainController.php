<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\TrackingDomain;
use App\Services\BlacklistService;
use App\Services\CloudflareService;
use App\Services\ConfigSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TrackingDomainController extends Controller
{
    public function index(CloudflareService $cloudflare)
    {
        return Inertia::render('TrackingDomains/Index', [
            'domains' => TrackingDomain::with('partner')->orderBy('domain')->paginate(20),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'cloudflareConfigured' => $cloudflare->isConfigured(),
            'trackerIp' => config('cloudflare.tracker_ip'),
        ]);
    }

    public function store(Request $request, ConfigSyncService $sync, CloudflareService $cloudflare)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255|unique:tracking_domains,domain',
            'partner_id' => 'nullable|exists:partners,id',
            'status' => 'required|in:active,paused,blacklisted',
            'check_interval_hours' => 'nullable|integer|min:1',
            'bot_fight_mode' => 'boolean',
            'provision_cloudflare' => 'boolean',
        ]);

        $data['domain'] = Str::lower(trim($data['domain']));
        $data['cloudflare_proxied'] = true;
        $data['bot_fight_mode'] = $request->boolean('bot_fight_mode');

        $domain = TrackingDomain::create($data);

        if ($request->boolean('provision_cloudflare', true)) {
            try {
                $cloudflare->provisionDomain($domain);
            } catch (\Throwable $e) {
                return redirect()->route('tracking-domains.index')
                    ->with('error', 'Dominio creado pero Cloudflare falló: '.$e->getMessage());
            }
        }

        $sync->syncDomain($domain->fresh());

        return redirect()->route('tracking-domains.index')->with('success', 'Dominio creado y sincronizado con Cloudflare.');
    }

    public function update(Request $request, TrackingDomain $trackingDomain, ConfigSyncService $sync, CloudflareService $cloudflare)
    {
        $data = $request->validate([
            'partner_id' => 'nullable|exists:partners,id',
            'status' => 'required|in:active,paused,blacklisted',
            'check_interval_hours' => 'nullable|integer|min:1',
            'bot_fight_mode' => 'boolean',
            'reprovision_cloudflare' => 'boolean',
        ]);

        $botFightChanged = $request->has('bot_fight_mode')
            && $request->boolean('bot_fight_mode') !== $trackingDomain->bot_fight_mode;

        $trackingDomain->update([
            'partner_id' => $data['partner_id'] ?? null,
            'status' => $data['status'],
            'check_interval_hours' => $data['check_interval_hours'] ?? $trackingDomain->check_interval_hours,
            'bot_fight_mode' => $request->boolean('bot_fight_mode'),
            'cloudflare_proxied' => true,
        ]);

        try {
            if ($request->boolean('reprovision_cloudflare') || ! $trackingDomain->cloudflare_record_id) {
                $cloudflare->provisionDomain($trackingDomain->fresh());
            } elseif ($botFightChanged) {
                $cloudflare->syncBotFightMode($trackingDomain->fresh());
            }
        } catch (\Throwable $e) {
            return redirect()->route('tracking-domains.index')
                ->with('error', 'Dominio actualizado pero Cloudflare falló: '.$e->getMessage());
        }

        $sync->syncDomain($trackingDomain->fresh());

        return redirect()->route('tracking-domains.index')->with('success', 'Dominio actualizado.');
    }

    public function reprovision(TrackingDomain $trackingDomain, ConfigSyncService $sync, CloudflareService $cloudflare)
    {
        try {
            $cloudflare->provisionDomain($trackingDomain);
            $sync->syncDomain($trackingDomain->fresh());
        } catch (\Throwable $e) {
            return back()->with('error', 'Cloudflare: '.$e->getMessage());
        }

        return back()->with('success', 'DNS reprovisionado en Cloudflare (proxy activo).');
    }

    public function check(TrackingDomain $trackingDomain, BlacklistService $blacklist)
    {
        $blacklist->checkDomain($trackingDomain);

        return redirect()->route('tracking-domains.index')->with('success', 'Chequeo de blacklist ejecutado.');
    }

    public function destroy(TrackingDomain $trackingDomain, CloudflareService $cloudflare)
    {
        try {
            $cloudflare->removeDnsRecord($trackingDomain);
        } catch (\Throwable) {
            // No bloquear borrado local si CF falla
        }

        $trackingDomain->delete();

        return redirect()->route('tracking-domains.index')->with('success', 'Dominio eliminado.');
    }
}
