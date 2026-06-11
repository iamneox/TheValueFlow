<?php

namespace App\Jobs;

use App\Models\Conversion;
use App\Models\Postback;
use App\Models\PostbackLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DispatchPostbackJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public Postback $postback, public Conversion $conversion) {}

    public function handle(): void
    {
        $url = $this->buildUrl($this->postback->url, $this->conversion);

        $log = PostbackLog::create([
            'postback_id' => $this->postback->id,
            'conversion_id' => $this->conversion->id,
            'url' => $url,
            'attempt' => $this->attempts(),
            'status' => 'pending',
        ]);

        try {
            $response = Http::timeout(15)->get($url);
            $log->update([
                'http_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 2000),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if (! $response->successful()) {
                $this->release(60 * $this->attempts());
            }
        } catch (\Throwable $e) {
            $log->update([
                'response_body' => $e->getMessage(),
                'status' => 'failed',
            ]);
            $this->release(60 * $this->attempts());
        }
    }

    protected function buildUrl(string $template, Conversion $conversion): string
    {
        $replacements = [
            '{click_id}' => $conversion->click_id,
            '{transaction_id}' => $conversion->transaction_id,
            '{payout}' => $conversion->payout,
            '{offer_id}' => $conversion->offer_id,
            '{partner_id}' => $conversion->partner_id,
        ];

        $url = $template;
        foreach ($replacements as $key => $value) {
            $url = str_replace($key, (string) $value, $url);
        }

        return $url;
    }
}
