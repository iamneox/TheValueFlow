<?php

namespace App\Http\Controllers;

use App\Models\CustomCap;
use App\Models\CustomPayout;
use App\Models\Offer;
use App\Models\OfferPartnerAccess;
use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('CustomSettings/Index', [
            'payouts' => CustomPayout::with(['partner', 'offer'])->latest()->paginate(10, ['*'], 'payouts_page'),
            'caps' => CustomCap::with(['partner', 'offer'])->latest()->paginate(10, ['*'], 'caps_page'),
            'access' => OfferPartnerAccess::with(['partner', 'offer'])->latest()->paginate(10, ['*'], 'access_page'),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storePayout(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'offer_id' => 'nullable|exists:offers,id',
            'event_name' => 'nullable|string',
            'payout' => 'required|numeric|min:0',
        ]);

        CustomPayout::create($data);

        return back()->with('success', 'Custom payout creado.');
    }

    public function storeCap(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'offer_id' => 'nullable|exists:offers,id',
            'cap_type' => 'required|in:daily,monthly',
            'metric' => 'required|in:clicks,conversions,revenue',
            'value' => 'required|integer|min:1',
        ]);

        CustomCap::create($data);

        return back()->with('success', 'Custom cap creado.');
    }

    public function storeAccess(Request $request)
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'partner_id' => 'required|exists:partners,id',
            'access_type' => 'required|in:whitelist,blacklist',
        ]);

        OfferPartnerAccess::updateOrCreate(
            ['offer_id' => $data['offer_id'], 'partner_id' => $data['partner_id']],
            ['access_type' => $data['access_type']]
        );

        return back()->with('success', 'Acceso de partner actualizado.');
    }
}
