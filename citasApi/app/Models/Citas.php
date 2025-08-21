<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $fillable = [
        'fecha_cita',
        'hora_cita',
        'lugar',
        'id_doctor',
        'id_paciente',
    ];
    public function paciente(){
        return $this->belongsTo(Pacientes::class, 'id_paciente');
    }
    public function doctor(){
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }
}
