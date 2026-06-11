<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        return Inertia::render('Clients/Index', [
            'clients' => Client::orderBy('company')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|in:net_10,net_20,net_30',
            'status' => 'required|in:active,paused,pending',
            'notes' => 'nullable|string',
        ]);

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Anunciante creado.');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'company' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|in:net_10,net_20,net_30',
            'status' => 'required|in:active,paused,pending',
            'notes' => 'nullable|string',
        ]);

        $client->update($data);

        return redirect()->route('clients.index')->with('success', 'Anunciante actualizado.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Anunciante eliminado.');
    }
}
