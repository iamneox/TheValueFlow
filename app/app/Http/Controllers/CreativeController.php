<?php

namespace App\Http\Controllers;

use App\Models\Creative;
use App\Models\Offer;
use App\Models\Partner;
use App\Models\TrafficSource;
use App\Services\HtmlKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreativeController extends Controller
{
    public function index()
    {
        return Inertia::render('Creatives/Index', [
            'creatives' => Creative::with('offer')->latest()->paginate(20),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'sources' => TrafficSource::with('partner')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'type' => 'required|in:banner,image,html,email,zip',
            'name' => 'required|string|max:255',
            'html_content' => 'nullable|string',
            'subject' => 'nullable|string',
            'sender_name' => 'nullable|string',
            'mandatory_mentions' => 'nullable|string',
            'status' => 'required|in:active,paused',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('creatives', 'public');
        }

        Creative::create($data);

        return redirect()->route('creatives.index')->with('success', 'Creative creado.');
    }

    public function downloadKit(Request $request, Creative $creative, HtmlKitService $kitService): StreamedResponse
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'traffic_source_id' => 'nullable|exists:traffic_sources,id',
        ]);

        $offer = Offer::findOrFail($creative->offer_id);
        $partner = Partner::findOrFail($data['partner_id']);
        $source = isset($data['traffic_source_id']) ? TrafficSource::find($data['traffic_source_id']) : null;

        $html = $kitService->processKit($creative, $offer, $partner, $source);

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $creative->name.'-'.$partner->name.'.html', ['Content-Type' => 'text/html']);
    }
}
