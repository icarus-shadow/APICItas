<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notificaciones;
use App\Enums\EstadoNotificacion;

class NotificacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fecha1 = now()->addDays(1)->format('Y-m-d');
        Notificaciones::create([
            'doctor_id' => 1,
            'fecha_solicitada' => $fecha1,
            'slots' => [['fecha' => $fecha1, 'hora' => '10:00-10:30'], ['fecha' => $fecha1, 'hora' => '11:00-11:30']],
            'estado' => EstadoNotificacion::PENDIENTE,
            'admin_id' => null,
        ]);

        $fecha2 = now()->addDays(2)->format('Y-m-d');
        Notificaciones::create([
            'doctor_id' => 2,
            'fecha_solicitada' => $fecha2,
            'slots' => [['fecha' => $fecha2, 'hora' => '14:00-14:30']],
            'estado' => EstadoNotificacion::PENDIENTE,
            'admin_id' => null,
        ]);

        $fecha3 = now()->addDays(7)->format('Y-m-d');
        Notificaciones::create([
            'doctor_id' => 3,
            'fecha_solicitada' => $fecha3,
            'slots' => [['fecha' => $fecha3, 'hora' => '09:00-09:30'], ['fecha' => $fecha3, 'hora' => '15:00-15:30']],
            'estado' => EstadoNotificacion::APROBADA,
            'admin_id' => 1,
        ]);
    }
}
