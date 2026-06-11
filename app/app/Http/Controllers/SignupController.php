<?php

namespace App\Http\Controllers;

use App\Models\ClientSignupRequest;
use App\Models\PartnerSignupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SignupController extends Controller
{
    public function partnerForm()
    {
        return Inertia::render('Signup/Partner');
    }

    public function partnerStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:partner_signup_requests,email',
            'phone' => 'nullable|string',
            'country' => 'nullable|string',
            'terms_accepted' => 'accepted',
        ]);

        PartnerSignupRequest::create($data);

        return back()->with('success', 'Solicitud enviada. Será revisada manualmente.');
    }

    public function clientForm()
    {
        return Inertia::render('Signup/Client');
    }

    public function clientStore(Request $request)
    {
        $data = $request->validate([
            'company' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|unique:client_signup_requests,email',
            'phone' => 'nullable|string',
            'terms_accepted' => 'accepted',
        ]);

        ClientSignupRequest::create($data);

        return back()->with('success', 'Solicitud enviada. Será revisada manualmente.');
    }

    public function adminIndex()
    {
        return Inertia::render('Signup/Admin', [
            'partnerRequests' => PartnerSignupRequest::latest()->paginate(10, ['*'], 'partners_page'),
            'clientRequests' => ClientSignupRequest::latest()->paginate(10, ['*'], 'clients_page'),
        ]);
    }

    public function approvePartner(PartnerSignupRequest $signupRequest)
    {
        $signupRequest->update(['status' => 'approved']);

        return back()->with('success', 'Solicitud de partner aprobada.');
    }

    public function approveClient(ClientSignupRequest $signupRequest)
    {
        $signupRequest->update(['status' => 'approved']);

        return back()->with('success', 'Solicitud de anunciante aprobada.');
    }
}
