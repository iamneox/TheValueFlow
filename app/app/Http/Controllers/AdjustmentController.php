<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\AdjustmentHistory;
use App\Models\Offer;
use App\Models\Partner;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdjustmentController extends Controller
{
    public function index()
    {
        return Inertia::render('Adjustments/Index', [
            'adjustments' => Adjustment::with(['offer', 'partner'])->latest()->paginate(20),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'partner_id' => 'required|exists:partners,id',
            'metric' => 'required|in:clicks,leads,revenue,payout',
            'value' => 'required|numeric',
            'transaction_id' => 'nullable|string',
            'method' => 'required|in:manual,transaction_id',
            'reason' => 'nullable|string',
        ]);

        $adjustment = Adjustment::create(array_merge($data, ['created_by' => $request->user()?->id]));

        AdjustmentHistory::create([
            'adjustment_id' => $adjustment->id,
            'old_values' => [],
            'new_values' => $adjustment->toArray(),
            'user_id' => $request->user()?->id,
        ]);

        $this->recalculateDraftInvoices($adjustment, $invoiceService);

        return redirect()->route('adjustments.index')->with('success', 'Ajuste creado.');
    }

    public function update(Request $request, Adjustment $adjustment, InvoiceService $invoiceService)
    {
        $old = $adjustment->toArray();
        $data = $request->validate([
            'metric' => 'required|in:clicks,leads,revenue,payout',
            'value' => 'required|numeric',
            'transaction_id' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $adjustment->update($data);

        AdjustmentHistory::create([
            'adjustment_id' => $adjustment->id,
            'old_values' => $old,
            'new_values' => $adjustment->toArray(),
            'user_id' => $request->user()?->id,
        ]);

        $this->recalculateDraftInvoices($adjustment, $invoiceService);

        return redirect()->route('adjustments.index')->with('success', 'Ajuste actualizado.');
    }

    protected function recalculateDraftInvoices(Adjustment $adjustment, InvoiceService $invoiceService): void
    {
        \App\Models\Invoice::where('partner_id', $adjustment->partner_id)
            ->where('status', 'draft')
            ->each(fn ($invoice) => $invoiceService->recalculate($invoice));
    }
}
