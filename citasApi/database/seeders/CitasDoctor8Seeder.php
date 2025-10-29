<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Citas;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CitasDoctor8Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el doctor 8 existe
        $doctorExists = DB::table('doctores')->where('id', 8)->exists();
        if (!$doctorExists) {
            // Si no existe, crear un doctor básico para pruebas
            DB::table('doctores')->insert([
                'id' => 8,
                'user_id' => 1, // Asumiendo user_id existente o ajustar según necesidad
                'cedula' => '888888888',
                'nombres' => 'Doctor',
                'apellidos' => 'Prueba',
                'id_especialidades' => 1,
                'lugar_trabajo' => 'Consultorio Prueba',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear citas para el doctor 8
        $now = Carbon::now();
        $citasData = [
            [
                'fecha_cita' => '2025-10-28', // Pasada
                'hora_cita' => '10:00',
                'lugar' => 'Consultorio 101',
                'id_doctor' => 8,
                'id_paciente' => 1, // Asumiendo paciente existente
                'tipo' => 'appointment',
                'estado' => 'completada', // Cita pasada
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'fecha_cita' => '2025-10-30', // Próxima (hoy o futura dependiendo de la fecha actual)
                'hora_cita' => '14:00',
                'lugar' => 'Consultorio 101',
                'id_doctor' => 8,
                'id_paciente' => 2,
                'tipo' => 'appointment',
                'estado' => Carbon::createFromFormat('Y-m-d H:i', '2025-10-30 14:00')->isPast() ? 'completada' : 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'fecha_cita' => '2025-11-01', // Futura
                'hora_cita' => '09:00',
                'lugar' => 'Consultorio 101',
                'id_doctor' => 8,
                'id_paciente' => 3,
                'tipo' => 'appointment',
                'estado' => 'pendiente', // Cita futura
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'fecha_cita' => '2025-11-02', // Futura adicional
                'hora_cita' => '11:00',
                'lugar' => 'Consultorio 101',
                'id_doctor' => 8,
                'id_paciente' => 1,
                'tipo' => 'appointment',
                'estado' => 'pendiente', // Cita futura
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'fecha_cita' => '2025-11-03', // Futura adicional
                'hora_cita' => '15:00',
                'lugar' => 'Consultorio 101',
                'id_doctor' => 8,
                'id_paciente' => 2,
                'tipo' => 'appointment',
                'estado' => 'pendiente', // Cita futura
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($citasData as $cita) {
            Citas::create($cita);
        }
    }
}
