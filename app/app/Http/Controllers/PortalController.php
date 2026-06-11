<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function partnerDashboard(Request $request, ReportService $reports)
    {
        $partnerId = $request->user()->partner_id;
        $filters = ['partner_id' => $partnerId, 'from' => now()->startOfMonth(), 'to' => now()];

        return Inertia::render('Portal/PartnerDashboard', [
            'summary' => $reports->aggregate($filters),
            'offers' => Offer::whereHas('trackingLinks', fn ($q) => $q->where('partner_id', $partnerId))->get(['id', 'name']),
        ]);
    }

    public function clientDashboard(Request $request, ReportService $reports)
    {
        $clientId = $request->user()->client_id;
        $filters = ['client_id' => $clientId, 'from' => now()->startOfMonth(), 'to' => now()];

        return Inertia::render('Portal/ClientDashboard', [
            'summary' => $reports->aggregate($filters),
            'offers' => Offer::where('client_id', $clientId)->get(['id', 'name']),
        ]);
    }
}
