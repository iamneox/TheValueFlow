<?php

namespace App\Services;

use App\Models\TrackingDomain;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudflareService
{
    public function isConfigured(): bool
    {
        return ! empty(config('cloudflare.api_token'));
    }

    /**
     * Crea o actualiza el registro DNS A con proxy activado y opcionalmente Bot Fight Mode en la zona.
     */
    public function provisionDomain(TrackingDomain $domain): TrackingDomain
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Cloudflare API no configurada (CLOUDFLARE_API_TOKEN).');
        }

        $hostname = Str::lower(trim($domain->domain));
        $zone = $this->findZoneForHostname($hostname);
        $recordName = $this->recordNameForZone($hostname, $zone['name']);
        $ip = config('cloudflare.tracker_ip');

        try {
            if ($domain->cloudflare_record_id && $domain->cloudflare_zone_id === $zone['id']) {
                $this->updateDnsRecord($zone['id'], $domain->cloudflare_record_id, $recordName, $ip, true);
            } else {
                $existing = $this->findExistingRecord($zone['id'], $recordName);
                if ($existing) {
                    $this->updateDnsRecord($zone['id'], $existing['id'], $recordName, $ip, true);
                    $domain->cloudflare_record_id = $existing['id'];
                } else {
                    $domain->cloudflare_record_id = $this->createDnsRecord($zone['id'], $recordName, $ip, true);
                }
            }

            $domain->cloudflare_zone_id = $zone['id'];
            $domain->cloudflare_proxied = true;

            if ($domain->bot_fight_mode) {
                $this->setBotFightMode($zone['id'], true);
            }

            $domain->cloudflare_synced_at = now();
            $domain->cloudflare_sync_error = null;
            $domain->save();

            return $domain;
        } catch (\Throwable $e) {
            $domain->cloudflare_sync_error = $e->getMessage();
            $domain->save();

            throw $e;
        }
    }

    public function syncBotFightMode(TrackingDomain $domain): void
    {
        if (! $this->isConfigured() || ! $domain->cloudflare_zone_id) {
            return;
        }

        $this->setBotFightMode($domain->cloudflare_zone_id, $domain->bot_fight_mode);
    }

    public function removeDnsRecord(TrackingDomain $domain): void
    {
        if (! $this->isConfigured() || ! $domain->cloudflare_zone_id || ! $domain->cloudflare_record_id) {
            return;
        }

        $this->request('DELETE', "/zones/{$domain->cloudflare_zone_id}/dns_records/{$domain->cloudflare_record_id}");
    }

    protected function findZoneForHostname(string $hostname): array
    {
        $response = $this->request('GET', '/zones', [
            'per_page' => 50,
            'status' => 'active',
        ]);

        $zones = $response['result'] ?? [];
        $matches = [];

        foreach ($zones as $zone) {
            $zoneName = Str::lower($zone['name']);
            if ($hostname === $zoneName || str_ends_with($hostname, '.'.$zoneName)) {
                $matches[] = $zone;
            }
        }

        if (empty($matches)) {
            throw new \RuntimeException("No se encontró zona Cloudflare para {$hostname}.");
        }

        // Preferir la zona más específica (más larga)
        usort($matches, fn ($a, $b) => strlen($b['name']) <=> strlen($a['name']));

        return $matches[0];
    }

    protected function recordNameForZone(string $hostname, string $zoneName): string
    {
        $zoneName = Str::lower($zoneName);
        $hostname = Str::lower($hostname);

        if ($hostname === $zoneName) {
            return $zoneName;
        }

        return Str::replaceLast('.'.$zoneName, '', $hostname);
    }

    protected function findExistingRecord(string $zoneId, string $name): ?array
    {
        $response = $this->request('GET', "/zones/{$zoneId}/dns_records", [
            'type' => 'A',
            'name' => $name,
        ]);

        return $response['result'][0] ?? null;
    }

    protected function createDnsRecord(string $zoneId, string $name, string $ip, bool $proxied): string
    {
        $response = $this->request('POST', "/zones/{$zoneId}/dns_records", [], [
            'type' => 'A',
            'name' => $name,
            'content' => $ip,
            'proxied' => $proxied,
            'ttl' => 1,
        ]);

        return $response['result']['id'];
    }

    protected function updateDnsRecord(string $zoneId, string $recordId, string $name, string $ip, bool $proxied): void
    {
        $this->request('PUT', "/zones/{$zoneId}/dns_records/{$recordId}", [], [
            'type' => 'A',
            'name' => $name,
            'content' => $ip,
            'proxied' => $proxied,
            'ttl' => 1,
        ]);
    }

    protected function setBotFightMode(string $zoneId, bool $enabled): void
    {
        $this->request('PATCH', "/zones/{$zoneId}/settings/bot_fight_mode", [], [
            'value' => $enabled ? 'on' : 'off',
        ]);
    }

    protected function request(string $method, string $path, array $query = [], array $body = []): array
    {
        $url = rtrim(config('cloudflare.api_base'), '/').'/'.ltrim($path, '/');

        $pending = Http::withToken(config('cloudflare.api_token'))
            ->acceptJson()
            ->timeout(30);

        try {
            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url, $query),
                'POST' => $pending->post($url, $body),
                'PUT' => $pending->put($url, $body),
                'PATCH' => $pending->patch($url, $body),
                'DELETE' => $pending->delete($url, $query),
                default => throw new \InvalidArgumentException("Método HTTP no soportado: {$method}"),
            };
        } catch (RequestException $e) {
            $errors = $e->response?->json('errors') ?? [];
            $message = collect($errors)->pluck('message')->implode('; ') ?: $e->getMessage();
            throw new \RuntimeException('Cloudflare API: '.$message, $e->getCode(), $e);
        }

        if (! $response->successful()) {
            $errors = $response->json('errors') ?? [];
            $message = collect($errors)->pluck('message')->implode('; ') ?: $response->body();
            throw new \RuntimeException('Cloudflare API: '.$message);
        }

        return $response->json();
    }
}
