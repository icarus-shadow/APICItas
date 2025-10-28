<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo DoctorHorario: Plantilla pura para horarios de doctores.
 * - Si 'fecha' es null: Representa una plantilla recurrente por día de la semana (dia).
 * - Si 'fecha' no es null: Representa un slot específico para esa fecha.
 * La disponibilidad se determina mediante las citas existentes en el modelo Citas.
 */
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
        'fecha',
        'status',
        'disponible'
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
            'fecha' => 'nullable|date',
            'status' => 'nullable|string|in:available,reserved,occupied',
            'disponible' => 'nullable|boolean',
        ];
    }

    /**
     * Verifica si este horario es una plantilla recurrente.
     */
    public function isTemplate()
    {
        return is_null($this->fecha);
    }

    /**
     * Verifica si este horario es un slot específico para una fecha.
     */
    public function isSpecificSlot()
    {
        return !is_null($this->fecha);
    }

    /**
     * Obtiene la disponibilidad de este slot verificando si hay una cita existente.
     */
    public function isAvailable()
    {
        if ($this->isTemplate()) {
            // Para plantillas, siempre disponible (la lógica de citas se maneja en Citas)
            return true;
        }

        // Para slots específicos, verificar si no hay cita en esa fecha y hora
        return !\App\Models\Citas::where('id_doctor', $this->id_doctor)
            ->where('fecha_cita', $this->fecha)
            ->where('hora_cita', $this->hora_inicio)
            ->exists();
    }

    /**
     * Libera el slot cambiando su status a 'available'.
     */
    public function releaseSlot()
    {
        $this->update(['status' => 'available']);
    }

}
