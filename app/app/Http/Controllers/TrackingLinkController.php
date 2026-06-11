<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Partner;
use App\Models\TrafficSource;
use App\Services\TrackingLinkService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingLinkController extends Controller
{
    public function index()
    {
        return Inertia::render('TrackingLinks/Index', [
            'links' => \App\Models\TrackingLink::with(['offer', 'partner', 'trackingDomain'])->latest()->paginate(20),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'sources' => TrafficSource::with('partner')->get(),
        ]);
    }

    public function store(Request $request, TrackingLinkService $service)
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'partner_id' => 'required|exists:partners,id',
            'traffic_source_id' => 'nullable|exists:traffic_sources,id',
            'aff_sub1' => 'nullable|string',
            'aff_sub2' => 'nullable|string',
            'aff_sub3' => 'nullable|string',
            'aff_sub4' => 'nullable|string',
            'aff_sub5' => 'nullable|string',
        ]);

        $offer = Offer::findOrFail($data['offer_id']);
        $partner = Partner::findOrFail($data['partner_id']);
        $source = isset($data['traffic_source_id']) ? TrafficSource::find($data['traffic_source_id']) : null;

        $service->generate($offer, $partner, $source, null, [
            'sub1' => $data['aff_sub1'] ?? null,
            'sub2' => $data['aff_sub2'] ?? null,
            'sub3' => $data['aff_sub3'] ?? null,
            'sub4' => $data['aff_sub4'] ?? null,
            'sub5' => $data['aff_sub5'] ?? null,
        ]);

        return redirect()->route('tracking-links.index')->with('success', 'Tracking link generado.');
    }
}
