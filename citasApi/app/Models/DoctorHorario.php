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
        'disponible',
        'status'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }

    public function horario()
    {
        return $this->belongsTo(Horarios::class, 'id_horario');
    }

    public static function rules()
    {
        return [
            'id_horario' => 'required|exists:horarios,id',
            'id_doctor' => 'required|exists:doctores,id',
            'dia' => 'required|integer|between:0,6',
            'hora_inicio' => 'required|string',
            'hora_fin' => 'required|string',
            'disponible' => 'boolean',
            'status' => 'string|in:available,booked,cancelled',
        ];
    }

    public function isAvailable()
    {
        return $this->status === 'available' && $this->disponible;
    }

    public function bookSlot()
    {
        $this->status = 'booked';
        $this->disponible = false;
        $this->save();
    }

    public function releaseSlot()
    {
        $this->status = 'available';
        $this->disponible = true;
        $this->save();
    }
}
