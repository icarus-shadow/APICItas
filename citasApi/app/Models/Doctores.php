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

    public function getAvailableSlots($date)
    {
        $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday
        if ($dayOfWeek == 0) $dayOfWeek = 7; // Adjust Sunday to 7

        return $this->doctorHorarios()
            ->where('dia', $dayOfWeek)
            ->where('status', 'available')
            ->where('disponible', true)
            ->get()
            ->map(function ($slot) use ($date) {
                $slot->fecha = $date;
                return $slot;
            });
    }
}
