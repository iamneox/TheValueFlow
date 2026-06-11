<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Partner;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with('partner')->latest();

        if ($user->hasRole('partner') && $user->partner_id) {
            $query->where('partner_id', $user->partner_id);
        }

        return Inertia::render('Invoices/Index', [
            'invoices' => $query->paginate(20),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Invoice $invoice)
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['partner', 'lines']),
        ]);
    }

    public function store(Request $request, InvoiceService $service)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $partner = Partner::findOrFail($data['partner_id']);
        $invoice = $service->generate($partner, $data['period_start'], $data['period_end'], $request->user()?->id);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Cierre generado.');
    }

    public function recalculate(Invoice $invoice, InvoiceService $service)
    {
        $service->recalculate($invoice);

        return back()->with('success', 'Cierre recalculado.');
    }

    public function downloadPdf(Invoice $invoice, InvoiceService $service)
    {
        return $service->generatePdf($invoice)->download('invoice-'.$invoice->number.'.pdf');
    }

    public function updateLine(Request $request, Invoice $invoice, InvoiceLine $line)
    {
        $data = $request->validate([
            'campaign_name' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'payout' => 'required|numeric|min:0',
        ]);

        $line->update([
            'campaign_name' => $data['campaign_name'],
            'quantity' => $data['quantity'],
            'payout' => $data['payout'],
            'total_payout' => $data['quantity'] * $data['payout'],
            'is_manual' => true,
        ]);

        $invoice->update(['total_amount' => $invoice->lines()->sum('total_payout')]);

        return back()->with('success', 'Línea actualizada.');
    }

    public function addLine(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'campaign_name' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'quantity_type' => 'required|in:leads,clicks,impressions',
            'payout' => 'required|numeric|min:0',
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'campaign_name' => $data['campaign_name'],
            'quantity' => $data['quantity'],
            'quantity_type' => $data['quantity_type'],
            'payout' => $data['payout'],
            'total_payout' => $data['quantity'] * $data['payout'],
            'is_manual' => true,
        ]);

        $invoice->update(['total_amount' => $invoice->lines()->sum('total_payout')]);

        return back()->with('success', 'Línea añadida.');
    }

    public function destroyLine(Invoice $invoice, InvoiceLine $line)
    {
        $line->delete();
        $invoice->update(['total_amount' => $invoice->lines()->sum('total_payout')]);

        return back()->with('success', 'Línea eliminada.');
    }

    public function markSent(Invoice $invoice)
    {
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', 'Cierre marcado como enviado.');
    }

    public function markPaid(Invoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Cierre marcado como pagado.');
    }
}
