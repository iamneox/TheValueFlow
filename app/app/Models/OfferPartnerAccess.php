<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPartnerAccess extends Model
{
    protected $table = 'offer_partner_access';

    protected $fillable = ['offer_id', 'partner_id', 'access_type'];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
