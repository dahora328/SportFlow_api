<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

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


    protected static function booted()
    {
        // Ao CRIAR um registro (creating), injeta o ID do usuário logado
        static::creating(function ($athlete) {
            if (Auth::check()) {
                $athlete->owner_id = Auth::id();
            }
        });

        // Opcional: Escopo global para que o usuário só veja SEUS atletas
        static::addGlobalScope('owner', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('owner_id', Auth::id());
            }
        });
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
