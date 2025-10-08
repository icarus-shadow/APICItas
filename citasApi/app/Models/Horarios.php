<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horarios extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'hora_inicio',
        'hora_fin',
        'dias'
    ];

    protected $casts = [
        'dias' => 'array',
    ];

    public function doctorHorarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DoctorHorario::class, 'id_horario');
    }
}
