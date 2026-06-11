<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSignupRequest extends Model
{
    protected $fillable = ['company', 'contact_name', 'email', 'phone', 'terms_accepted', 'status', 'notes'];

    protected function casts(): array
    {
        return ['terms_accepted' => 'boolean'];
    }
}
