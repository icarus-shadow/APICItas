<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha_cita',
        'hora_cita',
        'lugar',
        'id_doctor',
        'id_paciente'
    ];

    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }

    public function paciente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Pacientes::class, 'id_paciente');
    }

    public static function rules()
    {
        return [
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|string',
            'lugar' => 'required|string|max:255',
            'id_doctor' => 'required|exists:doctores,id',
            'id_paciente' => 'required|exists:pacientes,id',
        ];
    }

    public function isSlotAvailable()
    {
        return !self::where('id_doctor', $this->id_doctor)
            ->where('fecha_cita', $this->fecha_cita)
            ->where('hora_cita', $this->hora_cita)
            ->where('id', '!=', $this->id ?? 0) // exclude self if updating
            ->exists();
    }
}
