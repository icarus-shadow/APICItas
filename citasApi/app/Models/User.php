<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'id_rol'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];


    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Roles::class, 'id_rol');
    }

    public function paciente(): \Illuminate\Database\Eloquent\Relations\HasOne|User
    {
        return $this->hasOne(Pacientes::class);
    }

    public function doctor(): \Illuminate\Database\Eloquent\Relations\HasOne|User
    {
        return $this->hasOne(Doctores::class);
    }

    public function administrador(): \Illuminate\Database\Eloquent\Relations\HasOne|User
    {
        return $this->hasOne(Administradores::class);
    }
}
