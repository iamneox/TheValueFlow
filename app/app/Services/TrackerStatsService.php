<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackerStatsService
{
    public function bufferStatus(): array
    {
        $base = rtrim((string) config('tvf.tracker_internal_url'), '/');
        if ($base === '') {
            return ['reachable' => false, 'error' => 'TRACKER_INTERNAL_URL no configurado'];
        }

        try {
            $response = Http::timeout(5)->get("{$base}/internal/buffer-status");
            if (! $response->successful()) {
                return ['reachable' => false, 'error' => $response->body()];
            }

            return array_merge(['reachable' => true], $response->json());
        } catch (\Throwable $e) {
            Log::warning('tracker buffer status', ['error' => $e->getMessage()]);

            return ['reachable' => false, 'error' => $e->getMessage()];
        }
    }

    public function flush(): array
    {
        $base = rtrim((string) config('tvf.tracker_internal_url'), '/');
        if ($base === '') {
            return ['ok' => false, 'error' => 'TRACKER_INTERNAL_URL no configurado'];
        }

        try {
            $response = Http::timeout(30)->post("{$base}/internal/flush-stats");
            if (! $response->successful()) {
                return ['ok' => false, 'error' => $response->body()];
            }

            return array_merge(['ok' => true], $response->json());
        } catch (\Throwable $e) {
            Log::warning('tracker flush stats', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
