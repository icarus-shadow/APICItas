<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctores extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'especialidad',
        'horario',
        'lugar_trabajo'
    ];
    public function citas(){
        return $this->hasMany(Citas::class, 'id_doctor');
    }
}
