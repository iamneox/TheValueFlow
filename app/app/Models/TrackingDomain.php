<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingDomain extends Model
{
    protected $fillable = [
        'domain', 'partner_id', 'status', 'last_blacklist_check_at',
        'is_blacklisted', 'blacklist_details', 'check_interval_hours',
        'cloudflare_zone_id', 'cloudflare_record_id', 'cloudflare_proxied',
        'bot_fight_mode', 'cloudflare_synced_at', 'cloudflare_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_blacklisted' => 'boolean',
            'blacklist_details' => 'array',
            'last_blacklist_check_at' => 'datetime',
            'cloudflare_proxied' => 'boolean',
            'bot_fight_mode' => 'boolean',
            'cloudflare_synced_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function blacklistChecks(): HasMany
    {
        return $this->hasMany(DomainBlacklistCheck::class);
    }
}
