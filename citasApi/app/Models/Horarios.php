<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horarios extends Model
{
    protected $fillable = [
        'dia',
        'hora_inicio',
        'hora_fin',
        'id_doctor',
    ];
    public function doctor(){
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }
}
