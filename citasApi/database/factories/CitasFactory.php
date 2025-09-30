<?php

namespace Database\Factories;

use App\Models\Citas;
use App\Models\Doctores;
use App\Models\Pacientes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CitasFactory extends Factory
{
    protected $model = Citas::class;

    public function definition(): array
    {
        return [
            'fecha_cita' => $this->faker->word(),
            'hora_cita' => $this->faker->word(),
            'lugar' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'id_doctor' => Doctores::factory(),
            'id_paciente' => Pacientes::factory(),
        ];
    }
}
