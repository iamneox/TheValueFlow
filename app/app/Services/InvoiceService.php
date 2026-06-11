<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Offer;
use App\Models\Partner;
use App\Notifications\InvoiceCreatedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InvoiceService
{
    public function __construct(private ReportService $reportService) {}

    public function generate(Partner $partner, string $periodStart, string $periodEnd, ?int $createdBy = null): Invoice
    {
        $offers = Offer::whereHas('trackingLinks', fn ($q) => $q->where('partner_id', $partner->id))->get();

        $invoice = Invoice::create([
            'partner_id' => $partner->id,
            'number' => $this->nextNumber($partner),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'draft',
            'created_by' => $createdBy,
            'due_date' => $this->dueDate($partner, $periodEnd),
        ]);

        $total = 0;

        foreach ($offers as $offer) {
            $stats = $this->reportService->aggregate([
                'offer_id' => $offer->id,
                'partner_id' => $partner->id,
                'from' => $periodStart,
                'to' => $periodEnd,
            ]);

            $quantityType = match ($offer->type) {
                'CPL', 'CPA' => 'leads',
                'CPC' => 'clicks',
                'CPM' => 'impressions',
                default => 'leads',
            };

            $quantity = match ($quantityType) {
                'leads' => $stats['conversions'],
                'clicks' => $stats['gross_clicks'],
                'impressions' => $stats['impressions'],
            };

            $payout = (float) $offer->payout;
            $lineTotal = $quantity * $payout;
            $total += $lineTotal;

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'offer_id' => $offer->id,
                'campaign_name' => $offer->name,
                'quantity' => $quantity,
                'quantity_type' => $quantityType,
                'payout' => $payout,
                'total_payout' => $lineTotal,
            ]);
        }

        $invoice->update(['total_amount' => $total]);

        if ($partner->email) {
            Notification::route('mail', $partner->email)
                ->notify(new InvoiceCreatedNotification($invoice));
        }

        return $invoice->fresh('lines');
    }

    public function recalculate(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            return $invoice;
        }

        $invoice->lines()->where('is_manual', false)->delete();

        $partner = $invoice->partner;
        $offers = Offer::whereHas('trackingLinks', fn ($q) => $q->where('partner_id', $partner->id))->get();
        $total = (float) $invoice->lines()->where('is_manual', true)->sum('total_payout');

        foreach ($offers as $offer) {
            if ($invoice->lines()->where('offer_id', $offer->id)->where('is_manual', true)->exists()) {
                continue;
            }

            $stats = $this->reportService->aggregate([
                'offer_id' => $offer->id,
                'partner_id' => $partner->id,
                'from' => $invoice->period_start,
                'to' => $invoice->period_end,
            ]);

            $quantityType = match ($offer->type) {
                'CPL', 'CPA' => 'leads',
                'CPC' => 'clicks',
                'CPM' => 'impressions',
                default => 'leads',
            };

            $quantity = match ($quantityType) {
                'leads' => $stats['conversions'],
                'clicks' => $stats['gross_clicks'],
                'impressions' => $stats['impressions'],
            };

            $payout = (float) $offer->payout;
            $lineTotal = $quantity * $payout;
            $total += $lineTotal;

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'offer_id' => $offer->id,
                'campaign_name' => $offer->name,
                'quantity' => $quantity,
                'quantity_type' => $quantityType,
                'payout' => $payout,
                'total_payout' => $lineTotal,
            ]);
        }

        $invoice->update(['total_amount' => $total]);

        return $invoice->fresh('lines');
    }

    public function generatePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->load(['partner', 'lines']),
        ]);
    }

    protected function nextNumber(Partner $partner): string
    {
        $count = Invoice::where('partner_id', $partner->id)->count() + 1;

        return sprintf('TVF-%s-%04d', now()->format('Y'), $count);
    }

    protected function dueDate(Partner $partner, string $periodEnd): string
    {
        $days = match ($partner->payment_terms) {
            'net_10' => 10,
            'net_20' => 20,
            default => 30,
        };

        return now()->parse($periodEnd)->addDays($days)->toDateString();
    }
}
