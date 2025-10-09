<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Roles;
use App\Models\Especialidades;
use App\Models\Doctores;
use App\Models\Pacientes;
use App\Models\Administradores;
use App\Models\Citas;
use App\Models\DoctorHorario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear roles
        $roles = [
            ['id' => 1, 'nombre' => 'Paciente', 'descripcion' => 'Usuario paciente del sistema'],
            ['id' => 2, 'nombre' => 'Doctor', 'descripcion' => 'Profesional médico'],
            ['id' => 3, 'nombre' => 'Administrador', 'descripcion' => 'Administrador del sistema'],
        ];

        foreach ($roles as $role) {
            Roles::updateOrCreate(['id' => $role['id']], $role);
        }

        // Crear especialidades
        $especialidades = [
            ['nombre' => 'Medicina General', 'descripcion' => 'Atención médica general'],
            ['nombre' => 'Cardiología', 'descripcion' => 'Especialidad del corazón'],
            ['nombre' => 'Dermatología', 'descripcion' => 'Especialidad de la piel'],
            ['nombre' => 'Ginecología', 'descripcion' => 'Especialidad de la mujer'],
            ['nombre' => 'Pediatría', 'descripcion' => 'Especialidad de niños'],
            ['nombre' => 'Oftalmología', 'descripcion' => 'Especialidad de los ojos'],
        ];

        foreach ($especialidades as $especialidad) {
            Especialidades::create($especialidad);
        }

        // Crear usuario administrador
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@citas.com'],
            ['password' => Hash::make('password'), 'id_rol' => 3]
        );

        // Crear registro de administrador
        Administradores::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'user_id' => $adminUser->id,
                'nombres' => 'Administrador',
                'apellidos' => 'Sistema',
                'cedula' => '000000000',
                'telefono' => '3000000000'
            ]
        );

        // Crear doctores
        $doctoresData = [
            [
                'user' => ['email' => 'doctor1@citas.com', 'password' => Hash::make('password'), 'id_rol' => 2],
                'doctor' => ['cedula' => '123456789', 'nombres' => 'Juan', 'apellidos' => 'Pérez', 'id_especialidades' => 1, 'lugar_trabajo' => 'Clínica Central'],
            ],
            [
                'user' => ['email' => 'doctor2@citas.com', 'password' => Hash::make('password'), 'id_rol' => 2],
                'doctor' => ['cedula' => '987654321', 'nombres' => 'María', 'apellidos' => 'García', 'id_especialidades' => 2, 'lugar_trabajo' => 'Hospital General'],
            ],
            [
                'user' => ['email' => 'doctor3@citas.com', 'password' => Hash::make('password'), 'id_rol' => 2],
                'doctor' => ['cedula' => '456789123', 'nombres' => 'Carlos', 'apellidos' => 'Rodríguez', 'id_especialidades' => 3, 'lugar_trabajo' => 'Centro Médico'],
            ],
        ];

        $doctores = [];
        foreach ($doctoresData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );
            $doctor = Doctores::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($data['doctor'], ['user_id' => $user->id])
            );
            $doctores[] = $doctor;
        }

        // Crear pacientes
        $pacientesData = [
            [
                'user' => ['email' => 'paciente1@citas.com', 'password' => Hash::make('password'), 'id_rol' => 1],
                'paciente' => [
                    'nombres' => 'Ana',
                    'apellidos' => 'López',
                    'documento' => '1012345678',
                    'rh' => 'O+',
                    'fecha_nacimiento' => '1990-05-15',
                    'genero' => 'F',
                    'edad' => '34',
                    'telefono' => '3001234567',
                    'alergias' => 'Ninguna',
                    'comentarios' => 'Paciente regular'
                ],
            ],
            [
                'user' => ['email' => 'paciente2@citas.com', 'password' => Hash::make('password'), 'id_rol' => 1],
                'paciente' => [
                    'nombres' => 'Pedro',
                    'apellidos' => 'Martínez',
                    'documento' => '1023456789',
                    'rh' => 'A+',
                    'fecha_nacimiento' => '1985-08-20',
                    'genero' => 'M',
                    'edad' => '39',
                    'telefono' => '3012345678',
                    'alergias' => 'Penicilina',
                    'comentarios' => 'Paciente con alergia a penicilina'
                ],
            ],
            [
                'user' => ['email' => 'paciente3@citas.com', 'password' => Hash::make('password'), 'id_rol' => 1],
                'paciente' => [
                    'nombres' => 'Laura',
                    'apellidos' => 'Sánchez',
                    'documento' => '1034567890',
                    'rh' => 'B-',
                    'fecha_nacimiento' => '1995-12-10',
                    'genero' => 'F',
                    'edad' => '29',
                    'telefono' => '3023456789',
                    'alergias' => 'Ninguna',
                    'comentarios' => 'Paciente nueva'
                ],
            ],
        ];

        $pacientes = [];
        foreach ($pacientesData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );
            $paciente = Pacientes::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($data['paciente'], ['user_id' => $user->id])
            );
            $pacientes[] = $paciente;
        }

        // Crear citas
        $citasData = [
            [
                'id_paciente' => $pacientes[0]->id,
                'id_doctor' => $doctores[0]->id,
                'fecha_cita' => now()->addDays(1)->format('Y-m-d'),
                'hora_cita' => '10:00',
                'lugar' => 'Consultorio 101',
            ],
            [
                'id_paciente' => $pacientes[0]->id,
                'id_doctor' => $doctores[1]->id,
                'fecha_cita' => now()->addDays(3)->format('Y-m-d'),
                'hora_cita' => '14:30',
                'lugar' => 'Consultorio 205',
            ],
            [
                'id_paciente' => $pacientes[1]->id,
                'id_doctor' => $doctores[0]->id,
                'fecha_cita' => now()->subDays(2)->format('Y-m-d'),
                'hora_cita' => '09:00',
                'lugar' => 'Consultorio 101',
            ],
            [
                'id_paciente' => $pacientes[1]->id,
                'id_doctor' => $doctores[2]->id,
                'fecha_cita' => now()->addDays(5)->format('Y-m-d'),
                'hora_cita' => '11:15',
                'lugar' => 'Consultorio 303',
            ],
            [
                'id_paciente' => $pacientes[2]->id,
                'id_doctor' => $doctores[1]->id,
                'fecha_cita' => now()->addDays(7)->format('Y-m-d'),
                'hora_cita' => '16:00',
                'lugar' => 'Consultorio 205',
            ],
        ];

        foreach ($citasData as $citaData) {
            Citas::create($citaData);
        }

        // Crear plantillas de horarios
        $horariosTemplates = [
            ['nombre' => 'Horario Mañana L-V', 'hora_inicio' => '08:00', 'hora_fin' => '12:00', 'dias' => [1, 2, 3, 4, 5]],
            ['nombre' => 'Horario Tarde L-V', 'hora_inicio' => '14:00', 'hora_fin' => '18:00', 'dias' => [1, 2, 3, 4, 5]],
            ['nombre' => 'Horario Fin de Semana', 'hora_inicio' => '09:00', 'hora_fin' => '13:00', 'dias' => [6, 7]],
        ];

        $horarios = [];
        foreach ($horariosTemplates as $template) {
            $horarios[] = \App\Models\Horarios::create($template);
        }

        // Asignar horarios a doctores
        $asignaciones = [
            ['doctor' => $doctores[0], 'horario' => $horarios[0]], // Juan - Mañana
            ['doctor' => $doctores[1], 'horario' => $horarios[1]], // María - Tarde
            ['doctor' => $doctores[2], 'horario' => $horarios[2]], // Carlos - Fin de semana
        ];

        foreach ($asignaciones as $asignacion) {
            $this->assignHorarioToDoctor($asignacion['doctor'], $asignacion['horario']);
        }
    }

    private function assignHorarioToDoctor($doctor, $horarioTemplate)
    {
        $hora_inicio = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_inicio);
        $hora_fin = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_fin);
        $interval = new \DateInterval('PT30M');

        foreach ($horarioTemplate->dias as $dia) {
            $period = new \DatePeriod($hora_inicio, $interval, $hora_fin);

            foreach ($period as $start) {
                $end = clone $start;
                $end->add($interval);

                \App\Models\DoctorHorario::create([
                    'id_horario' => $horarioTemplate->id,
                    'id_doctor' => $doctor->id,
                    'dia' => $dia,
                    'hora_inicio' => $start->format('H:i'),
                    'hora_fin' => $end->format('H:i'),
                    'disponible' => true,
                ]);
            }
        }
    }
}
