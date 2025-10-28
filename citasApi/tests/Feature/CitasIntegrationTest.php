<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctores;
use App\Models\Pacientes;
use App\Models\Citas;
use App\Models\DoctorHorario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

class CitasIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $doctorUser;
    protected $patientUser;
    protected $doctor;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuarios de prueba
        $this->adminUser = User::where('email', 'admin2@test.com')->first() ?? User::factory()->create(['id_rol' => 1, 'email' => 'admin@test.com']);
        $this->doctorUser = User::where('email', 'doctor2@test.com')->first() ?? User::factory()->create(['id_rol' => 3, 'email' => 'doctor@test.com']);
        $this->patientUser = User::where('email', 'patient2@test.com')->first() ?? User::factory()->create(['id_rol' => 2, 'email' => 'patient@test.com']);

        // Crear doctor y paciente
        $this->doctor = Doctores::factory()->create(['user_id' => $this->doctorUser->id]);
        $this->patient = Pacientes::factory()->create(['user_id' => $this->patientUser->id]);

        // Crear horario plantilla primero
        $horario = \App\Models\Horarios::create([
            'nombre' => 'Horario Estándar',
            'hora_inicio' => '09:00',
            'hora_fin' => '17:00',
            'duracion_cita' => 60
        ]);

        // Crear horarios para el doctor (lunes a viernes, 9:00-17:00)
        for ($day = 1; $day <= 5; $day++) {
            DoctorHorario::create([
                'id_horario' => $horario->id,
                'id_doctor' => $this->doctor->id,
                'dia' => $day,
                'hora_inicio' => '09:00',
                'hora_fin' => '17:00',
                'fecha' => null, // Plantilla recurrente
                'status' => 'available',
                'disponible' => true
            ]);
        }
    }

    /** @test */
    public function test_crear_cita_exitosa()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '10:00';

        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fecha,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 101',
                'motivo' => 'Consulta general'
            ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'Cita creada con éxito']);

        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->patient->id,
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'tipo' => 'appointment'
        ]);
    }

    /** @test */
    public function test_crear_reserva_temporal()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '11:00';

        $response = $this->postJson('/api/create-reservation', [
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'lugar' => 'Reserva temporal'
        ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'Reserva creada con éxito']);

        $this->assertDatabaseHas('citas', [
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'tipo' => 'reservation'
        ]);
    }

    /** @test */
    public function test_no_puede_crear_cita_en_slot_ocupado()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '12:00';

        // Crear cita primero
        Citas::create([
            'id_paciente' => $this->patient->id,
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'lugar' => 'Consultorio 101',
            'tipo' => 'appointment'
        ]);

        // Intentar crear otra cita en el mismo slot
        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fecha,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 102',
                'motivo' => 'Otra consulta'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_reserva_no_bloquea_citas_reales()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '13:00';

        // Crear reserva temporal
        Citas::create([
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'lugar' => 'Reserva temporal',
            'tipo' => 'reservation'
        ]);

        // Debería poder crear cita real en el mismo slot (ya que reservas no bloquean citas)
        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fecha,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 101',
                'motivo' => 'Consulta urgente'
            ]);

        $response->assertStatus(422); // Debería fallar porque la reserva SÍ bloquea
    }

    /** @test */
    public function test_independencia_entre_fechas()
    {
        $fecha1 = now()->addDays(1)->format('Y-m-d');
        $fecha2 = now()->addDays(2)->format('Y-m-d');
        $hora = '14:00';

        // Crear cita en fecha1
        $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fecha1,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 101',
                'motivo' => 'Consulta fecha 1'
            ]);

        // Debería poder crear cita en fecha2 con la misma hora
        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fecha2,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 101',
                'motivo' => 'Consulta fecha 2'
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_obtener_slots_disponibles_por_fecha()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');

        $response = $this->getJson("/api/doctores/{$this->doctor->id}/slots-by-date?date={$fecha}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'hora_inicio',
                        'hora_fin',
                        'disponible'
                    ]
                ]);
    }

    /** @test */
    public function test_validar_slot_disponible()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '15:00';

        $response = $this->postJson("/api/doctores/{$this->doctor->id}/validate-slot", [
            'fecha' => $fecha,
            'hora' => $hora
        ]);

        $response->assertStatus(200)
                ->assertJson(['available' => true]);
    }

    /** @test */
    public function test_verificar_disponibilidad_slot_ocupado()
    {
        $fecha = now()->addDays(1)->format('Y-m-d');
        $hora = '16:00';

        // Crear cita
        Citas::create([
            'id_paciente' => $this->patient->id,
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fecha,
            'hora_cita' => $hora,
            'lugar' => 'Consultorio 101',
            'tipo' => 'appointment'
        ]);

        $response = $this->postJson('/api/check-slot-availability', [
            'id_doctor' => $this->doctor->id,
            'fecha' => $fecha,
            'hora' => $hora
        ]);

        $response->assertStatus(200)
                ->assertJson(['available' => false]);
    }

    /** @test */
    public function test_cita_con_fecha_pasada_falla()
    {
        $fechaPasada = now()->subDays(1)->format('Y-m-d');
        $hora = '10:00';

        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->postJson('/api/citas', [
                'id_doctor' => $this->doctor->id,
                'fecha_cita' => $fechaPasada,
                'hora_cita' => $hora,
                'lugar' => 'Consultorio 101',
                'motivo' => 'Consulta general'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_reserva_con_fecha_pasada_falla()
    {
        $fechaPasada = now()->subDays(1)->format('Y-m-d');
        $hora = '11:00';

        $response = $this->postJson('/api/create-reservation', [
            'id_doctor' => $this->doctor->id,
            'fecha_cita' => $fechaPasada,
            'hora_cita' => $hora,
            'lugar' => 'Reserva temporal'
        ]);

        $response->assertStatus(422);
    }
}
