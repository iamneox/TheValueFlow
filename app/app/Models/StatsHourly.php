<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatsHourly extends Model
{
    public $timestamps = false;

    protected $table = 'stats_hourly';

    protected $fillable = [
        'hour', 'offer_id', 'partner_id', 'source_id', 'country', 'device', 'os', 'browser', 'city', 'dimensions_hash',
        'impressions', 'gross_clicks', 'unique_clicks', 'duplicate_clicks', 'invalid_clicks',
        'conversions', 'revenue', 'payout',
    ];

    protected function casts(): array
    {
        return [
            'hour' => 'datetime',
            'revenue' => 'decimal:4',
            'payout' => 'decimal:4',
        ];
    }
}
