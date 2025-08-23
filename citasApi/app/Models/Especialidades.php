<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidades extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
    ];
    public function doctores(){
        return $this->hasMany(Doctores::class, 'id_especialidad');
    }
}
