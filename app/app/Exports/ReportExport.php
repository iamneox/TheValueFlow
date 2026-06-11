<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $data) {}

    public function collection(): Collection
    {
        return $this->data->map(fn ($row) => [
            'offer_id' => $row->offer_id,
            'impressions' => $row->impressions,
            'gross_clicks' => $row->gross_clicks,
            'unique_clicks' => $row->unique_clicks,
            'conversions' => $row->conversions,
            'revenue' => $row->revenue,
            'payout' => $row->payout,
        ]);
    }

    public function headings(): array
    {
        return ['Offer ID', 'Impressions', 'Gross Clicks', 'Unique Clicks', 'Conversions', 'Revenue', 'Payout'];
    }
}
