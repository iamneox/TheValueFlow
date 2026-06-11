<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Partner;
use App\Services\ReportService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(ReportService $reports)
    {
        $kpis = $reports->dashboardKpis();

        $topOffers = $kpis['top_offers']->map(function ($row) {
            $offer = Offer::find($row->offer_id);

            return [
                'id' => $row->offer_id,
                'name' => $offer?->name ?? 'N/A',
                'revenue' => $row->revenue,
                'payout' => $row->payout,
                'conversions' => $row->conversions,
            ];
        });

        $topPartners = $kpis['top_partners']->map(function ($row) {
            $partner = Partner::find($row->partner_id);

            return [
                'id' => $row->partner_id,
                'name' => $partner?->name ?? 'N/A',
                'revenue' => $row->revenue,
                'payout' => $row->payout,
                'conversions' => $row->conversions,
            ];
        });

        return Inertia::render('Dashboard', [
            'todayRevenue' => $kpis['today_revenue'],
            'todayPayout' => $kpis['today_payout'],
            'topOffers' => $topOffers,
            'topPartners' => $topPartners,
            'currentMonth' => $kpis['current_month'],
            'previousMonth' => $kpis['previous_month'],
        ]);
    }
}
