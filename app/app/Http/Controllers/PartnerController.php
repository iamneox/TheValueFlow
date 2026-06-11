<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerController extends Controller
{
    public function index()
    {
        return Inertia::render('Partners/Index', [
            'partners' => Partner::orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,paused,pending',
            'payment_terms' => 'required|in:net_10,net_20,net_30',
            'notes' => 'nullable|string',
        ]);

        Partner::create($data);

        return redirect()->route('partners.index')->with('success', 'Partner creado.');
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,paused,pending',
            'payment_terms' => 'required|in:net_10,net_20,net_30',
            'notes' => 'nullable|string',
        ]);

        $partner->update($data);

        return redirect()->route('partners.index')->with('success', 'Partner actualizado.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner eliminado.');
    }
}
