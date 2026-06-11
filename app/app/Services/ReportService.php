<?php

namespace App\Services;

use App\Models\Adjustment;
use App\Models\StatsHourly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function query(array $filters = []): Builder
    {
        $q = StatsHourly::query();

        if (! empty($filters['from'])) {
            $q->where('hour', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('hour', '<=', $filters['to']);
        }
        if (! empty($filters['offer_id'])) {
            $q->where('offer_id', $filters['offer_id']);
        }
        if (! empty($filters['partner_id'])) {
            $q->where('partner_id', $filters['partner_id']);
        }
        if (! empty($filters['client_id'])) {
            $offerIds = DB::table('offers')->where('client_id', $filters['client_id'])->pluck('id');
            $q->whereIn('offer_id', $offerIds);
        }
        if (! empty($filters['country'])) {
            $q->where('country', $filters['country']);
        }
        if (! empty($filters['device'])) {
            $q->where('device', $filters['device']);
        }
        if (! empty($filters['os'])) {
            $q->where('os', $filters['os']);
        }
        if (! empty($filters['browser'])) {
            $q->where('browser', $filters['browser']);
        }
        if (! empty($filters['city'])) {
            $q->where('city', $filters['city']);
        }

        return $q;
    }

    public function aggregate(array $filters = []): array
    {
        $row = $this->query($filters)
            ->selectRaw('
                SUM(impressions) as impressions,
                SUM(gross_clicks) as gross_clicks,
                SUM(unique_clicks) as unique_clicks,
                SUM(duplicate_clicks) as duplicate_clicks,
                SUM(invalid_clicks) as invalid_clicks,
                SUM(conversions) as conversions,
                SUM(revenue) as revenue,
                SUM(payout) as payout
            ')
            ->first();

        $adjustments = $this->adjustmentTotals($filters);

        $revenue = (float) ($row->revenue ?? 0) + $adjustments['revenue'];
        $payout = (float) ($row->payout ?? 0) + $adjustments['payout'];
        $clicks = (int) ($row->gross_clicks ?? 0) + $adjustments['clicks'];
        $conversions = (int) ($row->conversions ?? 0) + $adjustments['leads'];

        return [
            'impressions' => (int) ($row->impressions ?? 0),
            'gross_clicks' => $clicks,
            'unique_clicks' => (int) ($row->unique_clicks ?? 0),
            'duplicate_clicks' => (int) ($row->duplicate_clicks ?? 0),
            'invalid_clicks' => (int) ($row->invalid_clicks ?? 0),
            'conversions' => $conversions,
            'revenue' => $revenue,
            'payout' => $payout,
            'margin' => $revenue - $payout,
            'epc' => $clicks > 0 ? $payout / $clicks : 0,
            'ecpm' => ($row->impressions ?? 0) > 0 ? ($revenue / $row->impressions) * 1000 : 0,
            'ctr' => ($row->impressions ?? 0) > 0 ? ($clicks / $row->impressions) * 100 : 0,
            'cr' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
        ];
    }

    public function byOffer(array $filters = []): Collection
    {
        return $this->query($filters)
            ->selectRaw('offer_id, SUM(impressions) as impressions, SUM(gross_clicks) as gross_clicks,
                SUM(unique_clicks) as unique_clicks, SUM(conversions) as conversions,
                SUM(revenue) as revenue, SUM(payout) as payout')
            ->groupBy('offer_id')
            ->orderByDesc('revenue')
            ->get();
    }

    public function byPartner(array $filters = []): Collection
    {
        return $this->query($filters)
            ->selectRaw('partner_id, SUM(impressions) as impressions, SUM(gross_clicks) as gross_clicks,
                SUM(unique_clicks) as unique_clicks, SUM(conversions) as conversions,
                SUM(revenue) as revenue, SUM(payout) as payout')
            ->groupBy('partner_id')
            ->orderByDesc('payout')
            ->get();
    }

    public function dashboardKpis(): array
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $prevMonthStart = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        $todayStats = $this->aggregate(['from' => $today, 'to' => now()]);
        $currentMonth = $this->aggregate(['from' => $monthStart, 'to' => now()]);
        $previousMonth = $this->aggregate(['from' => $prevMonthStart, 'to' => $prevMonthEnd]);

        return [
            'today_revenue' => $todayStats['revenue'],
            'today_payout' => $todayStats['payout'],
            'top_offers' => $this->byOffer(['from' => $monthStart])->take(10),
            'top_partners' => $this->byPartner(['from' => $monthStart])->take(10),
            'current_month' => $currentMonth,
            'previous_month' => $previousMonth,
        ];
    }

    protected function adjustmentTotals(array $filters): array
    {
        $q = Adjustment::query();

        if (! empty($filters['offer_id'])) {
            $q->where('offer_id', $filters['offer_id']);
        }
        if (! empty($filters['partner_id'])) {
            $q->where('partner_id', $filters['partner_id']);
        }

        $rows = $q->get();

        return [
            'clicks' => (float) $rows->where('metric', 'clicks')->sum('value'),
            'leads' => (float) $rows->where('metric', 'leads')->sum('value'),
            'revenue' => (float) $rows->where('metric', 'revenue')->sum('value'),
            'payout' => (float) $rows->where('metric', 'payout')->sum('value'),
        ];
    }
}
