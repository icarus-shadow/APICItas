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
     *    "id": 1,
     *    "id_paciente": 3,
     *    "id_doctor": 2,
     *    "id_horario": 5,
     *    "motivo": "Consulta general"
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
     * @bodyParam id_horario integer ID del horario disponible. Example: 5
     * @bodyParam motivo string Motivo de la cita. Example: Dolor de cabeza
     *
     * @response 201 {
     *    "id": 10,
     *    "id_paciente": 3,
     *    "id_doctor": 2,
     *    "id_horario": 5,
     *    "motivo": "Dolor de cabeza"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_horario' => 'required|exists:horarios,id',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $horario = Horarios::find($request->id_horario);

        if (!$horario->disponible) {
            return response()->json(['message' => 'Este horario no está disponible'], 400);
        }

        $id_doctor = $horario->id_doctor;

        $cita = Citas::create([
            'id_paciente' => auth()->user()->paciente->id,
            'id_doctor' => $id_doctor,
            'id_horario' => $horario->id,
            'motivo' => $request->motivo
        ]);

        // Marcar el horario como no disponible
        $horario->update(['disponible' => false]);

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
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update(['motivo' => $request->motivo]);

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
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update(['motivo' => $request->motivo]);

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
}
