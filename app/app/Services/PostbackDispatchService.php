<?php

namespace App\Services;

use App\Jobs\DispatchPostbackJob;
use App\Models\Conversion;
use App\Models\Postback;

class PostbackDispatchService
{
    public function dispatchForConversion(Conversion $conversion): void
    {
        if (! $conversion->partner_id) {
            return;
        }

        $postbacks = Postback::where('partner_id', $conversion->partner_id)
            ->where('is_active', true)
            ->where(function ($q) use ($conversion) {
                $q->where('type', 'global')
                    ->orWhere('offer_id', $conversion->offer_id);
            })
            ->get();

        foreach ($postbacks as $postback) {
            DispatchPostbackJob::dispatch($postback, $conversion);
        }
    }
}
