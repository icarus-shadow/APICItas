<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctores extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cedula',
        'nombres',
        'apellidos',
        'id_especialidades',
        'horario',
        'lugar_trabajo'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidades::class, 'id_especialidades');
    }

    public function citas()
    {
        return $this->hasMany(Citas::class, 'id_doctor');
    }

    public function doctorHorarios()
    {
        return $this->hasMany(DoctorHorario::class, 'id_doctor');
    }

    public static function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'cedula' => 'required|string|unique:doctores,cedula',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'id_especialidades' => 'required|exists:especialidades,id',
            'horario' => 'nullable|string',
            'lugar_trabajo' => 'nullable|string|max:255',
        ];
    }

    public function getAvailableSlots($startDate, $endDate = null)
    {
        $slots = collect();

        $currentDate = $startDate;
        $end = $endDate ?: $startDate;

        while ($currentDate <= $end) {
            $dayOfWeek = date('w', strtotime($currentDate)); // 0=Sunday, 6=Saturday
            if ($dayOfWeek == 0) $dayOfWeek = 7; // Adjust Sunday to 7

            $daySlots = $this->doctorHorarios()
                ->where('dia', $dayOfWeek)
                ->where('status', 'available')
                ->where('disponible', true)
                ->get()
                ->map(function ($slot) use ($currentDate) {
                    $slot->fecha = $currentDate;
                    return $slot;
                });

            $slots = $slots->merge($daySlots);

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

  return $slots;
    }

    public function getAvailableSlotsByDate($date)
    {
        $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday
        if ($dayOfWeek == 0) $dayOfWeek = 7; // Adjust Sunday to 7

        $slots = $this->doctorHorarios()
            ->where('dia', $dayOfWeek)
            ->where('status', 'available')
            ->where('disponible', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('fecha')
                      ->orWhere('fecha', $date);
            })
            ->get()
            ->filter(function ($slot) use ($date) {
                // Check if slot is not occupied by any appointment or reservation
                return !\App\Models\Citas::where('id_doctor', $this->id)
                    ->where('fecha_cita', $date)
                    ->where('hora_cita', $slot->hora_inicio)
                    ->exists();
            })
            ->map(function ($slot) use ($date) {
                return [
                    'id' => $slot->id,
                    'fecha' => $date,
                    'hora_inicio' => $slot->hora_inicio,
                    'hora_fin' => $slot->hora_fin,
                    'disponible' => true
                ];
            })
            ->values();

        return $slots;
    }
}
