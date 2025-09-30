<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Horarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CitasController extends Controller
{
    /**
     * @group Citas [ADMIN]
     *
     * Listar todas las citas
     *
     * Devuelve todas las citas registradas en el sistema.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "id_paciente": 3,
     *      "id_doctor": 2,
     *      "id_horario": 5,
     *      "motivo": "Consulta general"
     *   }
     * ]
     */
    public function index()
    {
        $citas = Citas::all();
        return response()->json($citas);
    }

    /**
     * @group Citas [ADMIN]
     *
     * Ver una cita
     *
     * Devuelve los detalles de una cita específica.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la cita. Example: 1
     *
     * @response 200 {
     *
     * }
     */
    public function show($id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }
        return response()->json($cita);
    }

    /**
     * @group Citas [PACIENTE]
     *
     * Crear cita
     *
     * Crea una cita en un horario disponible para el doctor seleccionado.
     *
     * @authenticated
     *
     * @bodyParam id_doctor integer required ID del doctor. Example: 2
     * @bodyParam fecha_cita date required Fecha de la cita (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required Hora de la cita (HH:MM). Example: 14:30
     * @bodyParam lugar string required Lugar de la cita. Example: Consultorio 101
     * @bodyParam motivo string required Motivo de la cita. Example: Dolor de cabeza
     *
     * @response 201 {
     *    "id": 10,
     *    "id_paciente": 3,
     *    "id_doctor": 2,
     *    "fecha_cita": "2025-10-01",
     *    "hora_cita": "14:30",
     *    "lugar": "Consultorio 101",
     *    "motivo": "Dolor de cabeza"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_paciente' => 'required|exists:pacientes,id',
            'id_doctor' => 'required|exists:doctores,id',
            'fecha_cita' => 'required|date|after:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita = Citas::create([
            'id_paciente' => $request->id_paciente,
            'id_doctor' => $request->id_doctor,
            'fecha_cita' => $request->fecha_cita,
            'hora_cita' => $request->hora_cita,
            'lugar' => $request->lugar,
        ]);

        return response()->json($cita, 201);
    }

    /**
     * @group Citas [PACIENTE]
     *
     * Listar mis citas
     *
     * Devuelve todas las citas del paciente autenticado.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "id_doctor": 2,
     *      "id_horario": 5,
     *      "motivo": "Consulta general"
     *   }
     * ]
     */
    public function listOwn()
    {
        $citas = Citas::where('id_paciente', auth()->user()->paciente->id)->get();
        return response()->json($citas);
    }

    /**
     * @group Citas [DOCTOR]
     *
     * Listar mis citas (Doctor)
     *
     * Devuelve todas las citas asignadas al doctor autenticado.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "id_paciente": 3,
     *      "id_horario": 5,
     *      "motivo": "Consulta general"
     *   }
     * ]
     */
    public function listDoctor()
    {
        $citas = Citas::where('id_doctor', auth()->user()->doctor->id)->get();
        return response()->json($citas);
    }

    /**
     * @group Citas [PACIENTE]
     *
     * Editar mi cita
     *
     * Permite al paciente editar el motivo de su propia cita.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la cita. Example: 1
     * @bodyParam motivo string Motivo actualizado. Example: Dolor de estómago
     *
     * @response 200 {
     *    "id": 1,
     *    "motivo": "Dolor de estómago"
     * }
     */
    public function updateOwn(Request $request, $id)
    {
        $cita = Citas::where('id', $id)
            ->where('id_paciente', auth()->user()->paciente->id)
            ->first();

        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada o no autorizada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_cita' => 'required|date|after:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update($request->only(['fecha_cita', 'hora_cita', 'lugar', 'motivo']));

        return response()->json($cita);
    }

    /**
     * @group Citas [PACIENTE]
     *
     * Eliminar mi cita
     *
     * Permite al paciente eliminar su cita y liberar el horario.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la cita. Example: 1
     *
     * @response 200 {
     *    "message": "Cita eliminada con éxito"
     * }
     */
    public function destroyOwn($id)
    {
        $cita = Citas::where('id', $id)
            ->where('id_paciente', auth()->user()->paciente->id)
            ->first();

        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada o no autorizada'], 404);
        }

        $cita->delete();

        return response()->json(['message' => 'Cita eliminada con éxito']);
    }

    /**
     * @group Citas [ADMIN]
     *
     * Editar cualquier cita
     *
     * Permite al administrador modificar el motivo de una cita.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la cita. Example: 1
     * @bodyParam motivo string Nuevo motivo. Example: Revisión general
     *
     * @response 200 {
     *    "id": 1,
     *    "motivo": "Revisión general"
     * }
     */
    public function update(Request $request, $id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_doctor' => 'required|exists:doctores,id',
            'fecha_cita' => 'required|date|after:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update($request->only(['id_doctor', 'fecha_cita', 'hora_cita', 'lugar']));

        return response()->json($cita);
    }

    /**
     * @group Citas [ADMIN]
     *
     * Eliminar una cita
     *
     * Permite al administrador eliminar una cita y liberar el horario.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la cita. Example: 1
     *
     * @response 200 {
     *    "message": "Cita eliminada con éxito"
     * }
     */
    public function destroy($id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $horario = Horarios::find($cita->id_horario);
        if ($horario) {
            $horario->update(['disponible' => true]);
        }

        $cita->delete();

        return response()->json(['message' => 'Cita eliminada con éxito']);
    }

    /**
     * @group Citas [ADMIN]
     *
     * Contar citas
     *
     * Devuelve el número total de citas registradas en el sistema.
     *
     * @authenticated
     *
     * @response 200 {
     *    "total": 150
     * }
     */
    public function countCitas()
    {
        $total = Citas::count();
        return response()->json(['total' => $total]);
    }
}
