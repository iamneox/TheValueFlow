<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Offer;
use App\Models\TrackingDomain;
use App\Services\ConfigSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function index()
    {
        return Inertia::render('Offers/Index', [
            'offers' => Offer::with(['client', 'trackingDomain'])->orderBy('name')->paginate(20),
            'clients' => Client::orderBy('company')->get(['id', 'company']),
            'domains' => TrackingDomain::where('status', 'active')->get(['id', 'domain']),
        ]);
    }

    public function store(Request $request, ConfigSyncService $sync)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:CPL,CPC,CPM,CPA',
            'category' => 'nullable|string',
            'country' => 'nullable|string',
            'revenue' => 'required|numeric|min:0',
            'payout' => 'required|numeric|min:0',
            'from_name_advertiser' => 'nullable|string',
            'status' => 'required|in:active,pending,paused',
            'tracking_domain_id' => 'nullable|exists:tracking_domains,id',
            'daily_cap' => 'nullable|integer|min:0',
            'monthly_cap' => 'nullable|integer|min:0',
            'cap_type' => 'nullable|in:clicks,conversions,revenue',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'allowed_countries' => 'nullable|array',
            'allowed_days' => 'nullable|array',
            'allowed_hours_start' => 'nullable|string',
            'allowed_hours_end' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::random(4);
        $offer = Offer::create($data);
        $sync->syncOffer($offer);

        return redirect()->route('offers.index')->with('success', 'Oferta creada.');
    }

    public function update(Request $request, Offer $offer, ConfigSyncService $sync)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:CPL,CPC,CPM,CPA',
            'category' => 'nullable|string',
            'country' => 'nullable|string',
            'revenue' => 'required|numeric|min:0',
            'payout' => 'required|numeric|min:0',
            'from_name_advertiser' => 'nullable|string',
            'status' => 'required|in:active,pending,paused',
            'tracking_domain_id' => 'nullable|exists:tracking_domains,id',
            'daily_cap' => 'nullable|integer|min:0',
            'monthly_cap' => 'nullable|integer|min:0',
            'cap_type' => 'nullable|in:clicks,conversions,revenue',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'allowed_countries' => 'nullable|array',
            'allowed_days' => 'nullable|array',
            'allowed_hours_start' => 'nullable|string',
            'allowed_hours_end' => 'nullable|string',
        ]);

        $offer->update($data);
        $sync->syncOffer($offer);

        return redirect()->route('offers.index')->with('success', 'Oferta actualizada.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('offers.index')->with('success', 'Oferta eliminada.');
    }
}
