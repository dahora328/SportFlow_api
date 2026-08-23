<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enterprise extends Model
{
    protected $fillable = [
        'name',
        'social_reason',
        'fantasy_name',
        'owner_name',
        'document',
        'foundation_date',
        'IE',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'logo_path',
        'active',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function athletes()
    {
        return $this->hasMany(Athlete::class);
    }
}
