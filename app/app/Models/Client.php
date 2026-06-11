<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'company', 'contact_name', 'email', 'phone', 'payment_terms', 'status', 'notes',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
