<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctores extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cedula',
        'nombres',
        'apellidos',
        'id_especialidades',
        'horario',
        'lugar_trabajo'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidades');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_doctor');
    }

    public function doctorHorarios()
    {
        return $this->hasMany(DoctorHorario::class, 'id_doctor');
    }
}
