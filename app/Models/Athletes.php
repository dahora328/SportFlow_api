<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Athletes extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'full_name',
        'birth_date',
        'marital_status',
        'gender',
        'document',
        'address',
        'number',
        'neighborhood',
        'zip_code',
        'state',
        'city',
        'mobile_phone',
        'secondary_phone',
        'email',
        'mother_name',
        'father_name',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
