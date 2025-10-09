<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorHorario extends Model
{
    use HasFactory;

    protected $table = 'doctor_horarios';

    protected $fillable = [
        'id_horario',
        'id_doctor',
        'dia',
        'hora_inicio',
        'hora_fin',
        'disponible'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }

    public function horario()
    {
        return $this->belongsTo(Horarios::class, 'id_horario');
    }
}
