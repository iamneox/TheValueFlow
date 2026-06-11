<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Offer;
use App\Models\TrackingDomain;
use App\Services\ConfigSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function index()
    {
        return Inertia::render('Offers/Index', [
            'offers' => Offer::with(['client', 'trackingDomain', 'paymentTypes'])
                ->orderBy('name')
                ->paginate(20),
            'clients' => Client::orderBy('company')->get(['id', 'company']),
            'domains' => TrackingDomain::where('status', 'active')->get(['id', 'domain']),
        ]);
    }

    public function store(Request $request, ConfigSyncService $sync)
    {
        $data = $this->validateOffer($request);
        $paymentTypes = $data['payment_types'];
        unset($data['payment_types']);

        $data['slug'] = Str::slug($data['name']).'-'.Str::random(4);
        $this->applyPrimaryPaymentFields($data, $paymentTypes);

        $offer = Offer::create($data);
        $this->syncPaymentTypes($offer, $paymentTypes);
        $sync->syncOffer($offer->fresh(['paymentTypes']));

        return redirect()->route('offers.index')->with('success', 'Oferta creada.');
    }

    public function update(Request $request, Offer $offer, ConfigSyncService $sync)
    {
        $data = $this->validateOffer($request);
        $paymentTypes = $data['payment_types'];
        unset($data['payment_types']);

        $this->applyPrimaryPaymentFields($data, $paymentTypes);

        $offer->update($data);
        $this->syncPaymentTypes($offer, $paymentTypes);
        $sync->syncOffer($offer->fresh(['paymentTypes']));

        return redirect()->route('offers.index')->with('success', 'Oferta actualizada.');
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return redirect()->route('offers.index')->with('success', 'Oferta eliminada.');
    }

    private function validateOffer(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'payment_types' => 'required|array|min:1',
            'payment_types.*.type' => [
                'required',
                Rule::in(['CPL', 'CPC', 'CPM', 'CPA']),
                'distinct',
            ],
            'payment_types.*.revenue' => 'required|numeric|min:0',
            'payment_types.*.payout' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'country' => 'nullable|string',
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
    }

    private function applyPrimaryPaymentFields(array &$data, array $paymentTypes): void
    {
        $primary = $paymentTypes[0];
        $data['type'] = $primary['type'];
        $data['revenue'] = $primary['revenue'];
        $data['payout'] = $primary['payout'];
    }

    private function syncPaymentTypes(Offer $offer, array $paymentTypes): void
    {
        $offer->paymentTypes()->delete();

        foreach ($paymentTypes as $paymentType) {
            $offer->paymentTypes()->create([
                'type' => $paymentType['type'],
                'revenue' => $paymentType['revenue'],
                'payout' => $paymentType['payout'],
            ]);
        }
    }
}
