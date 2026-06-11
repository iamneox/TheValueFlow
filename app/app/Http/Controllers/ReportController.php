<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Client;
use App\Models\Offer;
use App\Models\Partner;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $filters = $request->only(['from', 'to', 'offer_id', 'partner_id', 'client_id', 'country', 'device', 'os', 'browser', 'city']);

        return Inertia::render('Reports/Index', [
            'filters' => $filters,
            'summary' => $reports->aggregate($filters),
            'byOffer' => $reports->byOffer($filters),
            'byPartner' => $reports->byPartner($filters),
            'offers' => Offer::orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'clients' => Client::orderBy('company')->get(['id', 'company']),
        ]);
    }

    public function export(Request $request, ReportService $reports)
    {
        $filters = $request->only(['from', 'to', 'offer_id', 'partner_id', 'client_id', 'country', 'device', 'os', 'browser', 'city']);
        $format = $request->get('format', 'csv');
        $data = $reports->byOffer($filters);

        if ($format === 'xlsx') {
            return Excel::download(new ReportExport($data), 'report.xlsx');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.report', ['data' => $data, 'filters' => $filters]);

            return $pdf->download('report.pdf');
        }

        return Excel::download(new ReportExport($data), 'report.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
