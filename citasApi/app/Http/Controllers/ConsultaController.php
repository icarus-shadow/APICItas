<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Doctores;
use App\Models\Horarios;
use App\Models\Especialidades;
use App\Models\Pacientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultaController extends Controller
{
    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Doctores por especialidad
     *
     * Lista todos los doctores que pertenecen a una especialidad específica.
     *
     * @authenticated
     * @urlParam id integer ID de la especialidad. Example: 3
     *
     * @response 200 [
     *    { "id": 1, "nombres": "Juan", "apellidos": "López", "especialidad": "Cardiología" }
     * ]
     * @response 404 {
     *    "message": "Especialidad no encontrada"
     * }
     */
    public function doctoresPorEspecialidad($id)
    {
        $doctores = Doctores::where('id_especialidad', $id)->get();
        return response()->json($doctores);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Disponibilidad de un doctor
     *
     * Devuelve los horarios disponibles (disponible = true) de un doctor para agendar cita.
     *
     * @authenticated
     * @urlParam id integer ID del doctor. Example: 2
     *
     * @response 200 [
     *    { "dia": 1, "hora_inicio": "08:00", "hora_fin": "08:30", "disponible": true }
     * ]
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function disponibilidadDoctor($id)
    {
        $horarios = \App\Models\DoctorHorario::where('id_doctor', $id)->where('disponible', true)->get();
        return response()->json($horarios);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Mis citas
     *
     * Muestra el historial de citas del usuario autenticado (paciente).
     *
     * @authenticated
     *
     * @response 200 [
     *    { "id": 1, "fecha_cita": "2025-09-10", "hora_cita": "10:00", "lugar": "Consultorio 1", "motivo": "Chequeo general", "id_doctor": 1, "id_paciente": 1, "doctor": {...}, "paciente": {...} }
     * ]
     * @response 403 {
     *    "message": "Acceso denegado"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     * @response 500 {
     *    "message": "Error interno del servidor"
     * }
     */
    public function misCitas()
    {
        try {
            // Obtener el usuario autenticado
            $user = Auth::user();

            // Verificar que sea paciente (id_rol = 1)
            if ((int)$user->id_rol !== 1) {
                return response()->json(['message' => 'Acceso denegado'], 403);
            }

            // Buscar el paciente asociado al usuario
            $paciente = Pacientes::where('user_id', $user->id)->first();

            if (!$paciente) {
                return response()->json(['message' => 'Paciente no encontrado'], 404);
            }

            // Obtener citas del paciente con relaciones
            $citas = Citas::where('id_paciente', $paciente->id)
                ->with(['doctor', 'paciente'])
                ->orderBy('fecha_cita', 'desc')
                ->get()
                ->map(function ($cita) {
                    return [
                        'id' => $cita->id,
                        'fecha_cita' => $cita->fecha_cita,
                        'hora_cita' => $cita->hora_cita,
                        'lugar' => $cita->lugar,
                        'motivo' => $cita->motivo,
                        'id_doctor' => $cita->id_doctor,
                        'id_paciente' => $cita->id_paciente,
                        'doctor' => $cita->doctor,
                        'paciente' => $cita->paciente,
                        'created_at' => $cita->created_at,
                        'updated_at' => $cita->updated_at,
                    ];
                });

            return response()->json($citas);

        } catch (\Exception $e) {
            \Log::error('Error en misCitas: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }


    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Pacientes atendidos por doctor
     *
     * Devuelve un listado de pacientes que el doctor autenticado ha atendido, con filtro opcional por fechas.
     *
     * @authenticated
     * @queryParam fecha_inicio date Fecha inicial del filtro (opcional). Example: 2025-09-01
     * @queryParam fecha_fin date Fecha final del filtro (opcional). Example: 2025-09-30
     *
     * @response 200 [
     *    { "id": 1, "fecha_cita": "2025-09-05", "paciente": {...}, "doctor": {...} }
     * ]
     * @response 500 {
     *    "message": "Error interno del servidor"
     * }
     */
    public function pacientesPorDoctor(Request $request)
    {
        $user = Auth::user();
        $query = Citas::where('id_doctor', $user->doctor->id);

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        $citas = $query->with('paciente')->get();
        return response()->json($citas);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Reporte de citas por especialidad
     *
     * Devuelve el total de citas agrupadas por especialidad (solo para administradores).
     *
     * @authenticated
     *
     * @response 200 [
     *    { "especialidad": "Cardiología", "total_citas": 15 }
     * ]
     * @response 403 {
     *    "message": "Acceso denegado"
     * }
     */
    public function reporteCitasPorEspecialidad()
    {
        $data = Citas::selectRaw('especialidades.nombre as especialidad, COUNT(citas.id) as total_citas')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id')
            ->join('especialidades', 'doctores.id_especialidades', '=', 'especialidades.id')
            ->groupBy('especialidades.nombre')
            ->get();

        return response()->json($data);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Consulta
     *
     * Horarios compactados por doctor
     *
     * Devuelve los horarios del doctor agrupados en rangos grandes, en lugar de intervalos de 30 minutos.
     *
     * @authenticated
     * @urlParam id_doctor integer ID del doctor. Example: 2
     *
     * @response 200 [
     *    { "dia": 1, "rango": "08:00 - 12:00" }
     * ]
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function horariosCompactados($id_doctor)
    {
        $horarios = Horarios::where('id_doctor', $id_doctor)->orderBy('dia')->orderBy('hora_inicio')->get();

        $compactados = [];
        foreach ($horarios->groupBy('dia') as $dia => $items) {
            $primero = $items->first();
            $ultimo = $items->last();
            $compactados[] = [
                'dia' => $dia,
                'rango' => $primero->hora_inicio . ' - ' . $ultimo->hora_fin
            ];
        }

        return response()->json($compactados);
    }
}
