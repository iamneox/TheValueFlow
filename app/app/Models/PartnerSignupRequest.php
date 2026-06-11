<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerSignupRequest extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'country', 'terms_accepted', 'status', 'notes'];

    protected function casts(): array
    {
        return ['terms_accepted' => 'boolean'];
    }
}
