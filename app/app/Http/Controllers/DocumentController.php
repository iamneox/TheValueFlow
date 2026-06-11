<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index()
    {
        return Inertia::render('Documents/Index', [
            'documents' => Document::latest()->paginate(20),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'clients' => Client::orderBy('company')->get(['id', 'company']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entity_type' => 'required|in:partner,client',
            'entity_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'file' => 'required|file|max:20480',
        ]);

        $model = $data['entity_type'] === 'partner'
            ? Partner::findOrFail($data['entity_id'])
            : Client::findOrFail($data['entity_id']);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'documentable_type' => $model::class,
            'documentable_id' => $model->id,
            'name' => $data['name'],
            'file_path' => $path,
            'type' => $data['type'],
            'uploaded_by' => $request->user()?->id,
        ]);

        return redirect()->route('documents.index')->with('success', 'Documento subido.');
    }

    public function download(Document $document)
    {
        return Storage::disk('public')->download($document->file_path, $document->name);
    }
}
