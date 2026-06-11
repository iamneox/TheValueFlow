<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSource extends Model
{
    protected $fillable = ['partner_id', 'source_id', 'name', 'is_blocked'];

    protected function casts(): array
    {
        return ['is_blocked' => 'boolean'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
