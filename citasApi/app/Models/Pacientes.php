<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pacientes extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'rh',
        'fecha_nacimiento',
        'genero',
        'edad',
        'email',
        'telefono',
        'alergias',
        'comentarios'
    ];

    public function citas(){
        return $this->hasMany(Citas::class, 'id_paciente');
    }
}
