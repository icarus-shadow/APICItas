<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctores extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'id_especialidad',
        'horario',
        'lugar_trabajo'
    ];
    public function citas(){
        return $this->hasMany(Citas::class, 'id_doctor');
    }

    public function horarios(){
        return $this->hasMany(Horarios::class, 'id_doctor');
    }

    public function Especialidades()
    {
        return $this->belongsTo(Especialidades::class, 'id_especialidad');
    }
}
