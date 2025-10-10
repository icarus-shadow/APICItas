<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Horarios;
use App\Models\DoctorHorario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
        \Log::info('Intentando crear cita', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), Citas::rules());

        if ($validator->fails()) {
            \Log::error('Validación fallida en store', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dia = date('w', strtotime($request->fecha_cita));

        DB::transaction(function () use ($request, $dia) {
            // Find the available slot
            $slot = DoctorHorario::where('id_doctor', $request->id_doctor)
                ->where('dia', $dia)
                ->where('hora_inicio', '<=', $request->hora_cita)
                ->where('hora_fin', '>=', $request->hora_cita)
                ->where('status', 'available')
                ->where('disponible', true)
                ->first();

            if (!$slot) {
                \Log::warning('Slot no disponible', ['doctor' => $request->id_doctor, 'dia' => $dia, 'hora' => $request->hora_cita]);
                throw new \Exception('Slot no disponible');
            }

            // Book the slot
            $slot->bookSlot();
            \Log::info('Slot reservado', ['slot_id' => $slot->id]);

            // Create the cita
            $cita = Citas::create([
                'id_paciente' => $request->id_paciente,
                'id_doctor' => $request->id_doctor,
                'fecha_cita' => $request->fecha_cita,
                'hora_cita' => $request->hora_cita,
                'lugar' => $request->lugar,
            ]);

            \Log::info('Cita creada', ['cita_id' => $cita->id]);
        });

        return response()->json(['message' => 'Cita creada con éxito'], 201);
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
        \Log::info('Intentando actualizar cita propia', ['id' => $id, 'user_id' => auth()->id(), 'request' => $request->all()]);

        $cita = Citas::where('id', $id)
            ->where('id_paciente', auth()->user()->paciente->id)
            ->first();

        if (!$cita) {
            \Log::warning('Cita no encontrada o no autorizada', ['id' => $id, 'user_id' => auth()->id()]);
            return response()->json(['message' => 'Cita no encontrada o no autorizada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_cita' => 'required|date|after:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('Validación fallida en updateOwn', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldData = $cita->toArray();
        $cita->update($request->only(['fecha_cita', 'hora_cita', 'lugar', 'motivo']));
        $newData = $cita->fresh()->toArray();

        \Log::info('Cita actualizada exitosamente', ['old' => $oldData, 'new' => $newData]);

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

        DB::transaction(function () use ($cita) {
            // Release the slot
            $dia = date('w', strtotime($cita->fecha_cita));
            $slot = DoctorHorario::where('id_doctor', $cita->id_doctor)
                ->where('dia', $dia)
                ->where('hora_inicio', '<=', $cita->hora_cita)
                ->where('hora_fin', '>=', $cita->hora_cita)
                ->first();

            if ($slot) {
                $slot->releaseSlot();
            }

            $cita->delete();
        });

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

    /**
     * @group Citas [GENERAL]
     *
     * Obtener slots disponibles para un doctor en un rango de fechas
     *
     * Devuelve los slots disponibles para agendar citas.
     *
     * @urlParam doctorId integer ID del doctor. Example: 1
     * @queryParam startDate date Fecha de inicio (YYYY-MM-DD). Example: 2025-10-10
     * @queryParam endDate date Fecha de fin (YYYY-MM-DD). Example: 2025-10-10
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "fecha": "2025-10-10",
     *      "hora_inicio": "09:00",
     *      "hora_fin": "10:00",
     *      "disponible": true
     *   }
     * ]
     */
    public function getAvailableSlots(Request $request, $doctorId)
    {
        $doctor = \App\Models\Doctores::find($doctorId);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        if (!$startDate || !$endDate) {
            return response()->json(['message' => 'Fechas requeridas'], 400);
        }

        $slots = $doctor->getAvailableSlots($startDate);

        return response()->json($slots);
    }

    /**
     * @group Citas [GENERAL]
     *
     * Validar si un slot está disponible
     *
     * Verifica en tiempo real si un horario específico está disponible para un doctor.
     *
     * @urlParam doctorId integer ID del doctor. Example: 1
     * @bodyParam fecha date Fecha (YYYY-MM-DD). Example: 2025-10-10
     * @bodyParam hora time Hora (HH:MM). Example: 09:00
     *
     * @response 200 {
     *    "available": true
     * }
     */
    public function validateSlot(Request $request, $doctorId)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i'
        ]);

        $doctor = \App\Models\Doctores::find($doctorId);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }

        $dia = date('w', strtotime($request->fecha));

        $slot = \App\Models\DoctorHorario::where('id_doctor', $doctorId)
            ->where('dia', $dia)
            ->where('hora_inicio', '<=', $request->hora)
            ->where('hora_fin', '>', $request->hora)
            ->where('status', 'available')
            ->where('disponible', true)
            ->first();

        return response()->json(['available' => $slot ? true : false]);
    }
}
