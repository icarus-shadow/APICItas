<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctores;
use App\Models\User;

class DoctoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios doctores primero si no existen
        $user1 = User::firstOrCreate(
            ['email' => 'doctor1@example.com'],
            [
                'name' => 'Dr. Juan Pérez',
                'password' => bcrypt('password'),
                'role' => 'doctor'
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'doctor2@example.com'],
            [
                'name' => 'Dra. María González',
                'password' => bcrypt('password'),
                'role' => 'doctor'
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'doctor3@example.com'],
            [
                'name' => 'Dr. Carlos Rodríguez',
                'password' => bcrypt('password'),
                'role' => 'doctor'
            ]
        );

        // Crear doctores
        Doctores::create([
            'user_id' => $user1->id,
            'cedula' => '1234567890',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'id_especialidades' => 1, // Asumiendo que existe la especialidad 1
            'lugar_trabajo' => 'Consultorio 101',
            'telefono' => '3012345678'
        ]);

        Doctores::create([
            'user_id' => $user2->id,
            'cedula' => '0987654321',
            'nombres' => 'María',
            'apellidos' => 'González',
            'id_especialidades' => 2, // Asumiendo que existe la especialidad 2
            'lugar_trabajo' => 'Consultorio 202',
            'telefono' => '3023456789'
        ]);

        Doctores::create([
            'user_id' => $user3->id,
            'cedula' => '1122334455',
            'nombres' => 'Carlos',
            'apellidos' => 'Rodríguez',
            'id_especialidades' => 1,
            'lugar_trabajo' => 'Consultorio 303',
            'telefono' => '3034567890'
        ]);
    }
}
