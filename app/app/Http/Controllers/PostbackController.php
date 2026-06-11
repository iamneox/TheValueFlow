<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Partner;
use App\Models\Postback;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostbackController extends Controller
{
    public function index()
    {
        return Inertia::render('Postbacks/Index', [
            'postbacks' => Postback::with(['partner', 'offer'])->latest()->paginate(20),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'offer_id' => 'nullable|exists:offers,id',
            'url' => 'required|url',
            'type' => 'required|in:global,offer',
            'is_active' => 'boolean',
        ]);

        Postback::create($data);

        return redirect()->route('postbacks.index')->with('success', 'Postback creado.');
    }

    public function update(Request $request, Postback $postback)
    {
        $data = $request->validate([
            'url' => 'required|url',
            'is_active' => 'boolean',
        ]);

        $postback->update($data);

        return redirect()->route('postbacks.index')->with('success', 'Postback actualizado.');
    }

    public function destroy(Postback $postback)
    {
        $postback->delete();

        return redirect()->route('postbacks.index')->with('success', 'Postback eliminado.');
    }
}
