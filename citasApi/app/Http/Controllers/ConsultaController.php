<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Doctores;
use App\Models\Horarios;
use App\Models\Especialidades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultasController extends Controller
{
    /**
     * @group Consultas
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
     */
    public function doctoresPorEspecialidad($id)
    {
        $doctores = Doctores::where('id_especialidad', $id)->get();
        return response()->json($doctores);
    }

    /**
     * @group Consultas
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
     */
    public function disponibilidadDoctor($id)
    {
        $horarios = Horarios::where('id_doctor', $id)->where('disponible', true)->get();
        return response()->json($horarios);
    }

    /**
     * @group Consultas
     *
     * Mis citas
     *
     * Muestra el historial de citas del usuario autenticado (paciente o doctor).
     *
     * @authenticated
     *
     * @response 200 [
     *    { "fecha": "2025-09-10", "estado": "pendiente", "doctor": "Juan Pérez" }
     * ]
     */
    public function misCitas()
    {
        $user = Auth::user();
        if ($user->rol_id == 1) {
            $citas = Citas::where('id_paciente', $user->paciente->id)->get();
        } elseif ($user->rol_id == 2) {
            $citas = Citas::where('id_doctor', $user->doctor->id)->get();
        } else {
            $citas = [];
        }
        return response()->json($citas);
    }

    /**
     * @group Consultas
     *
     * Pacientes atendidos por doctor
     *
     * Devuelve un listado de pacientes que el doctor autenticado ha atendido, con filtro opcional por fechas.
     *
     * @authenticated
     * @queryParam fecha_inicio date Fecha inicial del filtro. Example: 2025-09-01
     * @queryParam fecha_fin date Fecha final del filtro. Example: 2025-09-30
     *
     * @response 200 [
     *    { "paciente": "María Gómez", "fecha": "2025-09-05" }
     * ]
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
     * @group Consultas [ADMIN]
     *
     * Reporte de citas por especialidad
     *
     * Devuelve el total de citas agrupadas por especialidad.
     *
     * @authenticated
     *
     * @response 200 [
     *    { "especialidad": "Cardiología", "total_citas": 15 }
     * ]
     */
    public function reporteCitasPorEspecialidad()
    {
        $data = Citas::selectRaw('especialidades.nombre as especialidad, COUNT(citas.id) as total_citas')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id')
            ->join('especialidades', 'doctores.id_especialidad', '=', 'especialidades.id')
            ->groupBy('especialidades.nombre')
            ->get();

        return response()->json($data);
    }

    /**
     * @group Consultas
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
