<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Athlete extends Model
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
        'enterprise_id',
        'photo_path',
    ];


    protected static function booted()
    {
        // Ao CRIAR um registro (creating), injeta o ID do usuário e a empresa
        static::creating(function ($athlete) {
            if (Auth::check()) {
                $user = Auth::user();
                $athlete->owner_id = $user->id;
                $athlete->enterprise_id = $user->enterprise_id;
            }
        });

        // Escopo global para que o usuário só veja os atletas da sua empresa
        static::addGlobalScope('enterprise', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->enterprise_id) {
                    $builder->where('enterprise_id', $user->enterprise_id);
                } else {
                    // Se o usuário não tem empresa, não deve ver nada
                    $builder->whereRaw('1 = 0');
                }
            }
        });
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}
