<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\TrafficSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TrafficSourceController extends Controller
{
    public function index()
    {
        return Inertia::render('TrafficSources/Index', [
            'sources' => TrafficSource::with('partner')->orderBy('name')->paginate(20),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'name' => 'required|string|max:255',
            'is_blocked' => 'boolean',
        ]);

        $data['source_id'] = Str::upper(Str::random(8));

        TrafficSource::create($data);

        return redirect()->route('traffic-sources.index')->with('success', 'Source creada.');
    }

    public function update(Request $request, TrafficSource $trafficSource)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_blocked' => 'boolean',
        ]);

        $trafficSource->update($data);

        return redirect()->route('traffic-sources.index')->with('success', 'Source actualizada.');
    }

    public function destroy(TrafficSource $trafficSource)
    {
        $trafficSource->delete();

        return redirect()->route('traffic-sources.index')->with('success', 'Source eliminada.');
    }
}
