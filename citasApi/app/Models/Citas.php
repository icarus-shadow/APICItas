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
        'tipo',
        'id_doctor',
        'id_paciente'
    ];

    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Doctores::class, 'id_doctor');
    }

    public function paciente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Pacientes::class, 'id_paciente')->nullable();
    }

    public static function rules()
    {
        return [
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|string',
            'lugar' => 'required|string|max:255',
            'tipo' => 'required|in:appointment,reservation',
            'id_doctor' => 'required|exists:doctores,id',
            'id_paciente' => 'nullable|exists:pacientes,id',
        ];
    }

    public static function rulesForReservation()
    {
        return [
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|string',
            'lugar' => 'sometimes|string|max:255',
            'tipo' => 'required|in:appointment,reservation',
            'id_doctor' => 'required|exists:doctores,id',
            'id_paciente' => 'nullable|exists:pacientes,id',
        ];
    }

    public function isSlotAvailable()
    {
        // Para citas reales ('appointment'), verificar que no haya ninguna cita (appointment o reservation) en ese slot
        if ($this->tipo === 'appointment') {
            return !self::where('id_doctor', $this->id_doctor)
                ->where('fecha_cita', $this->fecha_cita)
                ->where('hora_cita', $this->hora_cita)
                ->where('id', '!=', $this->id ?? 0) // exclude self if updating
                ->exists();
        }

        // Para reservas temporales ('reservation'), verificar que no haya citas reales en ese slot
        // Las reservas pueden coexistir con otras reservas, pero no con citas reales
        if ($this->tipo === 'reservation') {
            return !self::where('id_doctor', $this->id_doctor)
                ->where('fecha_cita', $this->fecha_cita)
                ->where('hora_cita', $this->hora_cita)
                ->where('tipo', 'appointment') // solo verificar contra citas reales
                ->where('id', '!=', $this->id ?? 0) // exclude self if updating
                ->exists();
        }

        // Si el tipo no es válido, considerar no disponible
        return false;
    }
}
