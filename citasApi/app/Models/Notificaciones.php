<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\EstadoNotificacion;

class Notificaciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'fecha_solicitada',
        'slots',
        'estado',
        'admin_id',
    ];

    protected $casts = [
        'slots' => 'array',
        'estado' => EstadoNotificacion::class,
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctores::class, 'doctor_id');
    }

    public function administrador()
    {
        return $this->belongsTo(Administradores::class, 'admin_id');
    }
}
