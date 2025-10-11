<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Horarios;
use App\Models\DoctorHorario;
use App\Models\Pacientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DoctorCitasController extends Controller
{
    public function __construct()
    {
    }

    /**
     * @group Citas
     * @subgroup Doctor
     *
     * List my appointments (Doctor)
     *
     * Retrieves all appointments assigned to the authenticated doctor.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "id_paciente": 3,
     *     "id_doctor": 2,
     *     "fecha_cita": "2025-10-01",
     *     "hora_cita": "14:30:00",
     *     "lugar": "Consultorio 101",
     *     "motivo": "Consulta general",
     *     "created_at": "2025-09-01T10:00:00.000000Z",
     *     "updated_at": "2025-09-01T10:00:00.000000Z"
     *   }
     * ]
     * @response 401 {"message": "Unauthenticated."}
     */
    public function listDoctor()
    {
        $user = auth()->user();
        if ($user->id_rol === 3) { // Administrador
            $citas = Citas::all();
        } else { // Doctor
            $citas = Citas::where('id_doctor', $user->doctor->id)->get();
        }
        return response()->json($citas);
    }

    /**
     * @group Citas
     * @subgroup Doctor
     *
     * Create appointment for doctor
     *
     * Creates a new appointment assigned to the authenticated doctor, automatically reserving the slot.
     *
     * @authenticated
     *
     * @bodyParam id_paciente integer required The ID of the patient. Example: 3
     * @bodyParam fecha_cita date required The appointment date (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required The appointment time (HH:MM). Example: 14:30
     * @bodyParam lugar string required The appointment location. Example: Consultorio 101
     * @bodyParam motivo string optional The reason for the appointment. Example: Consulta general
     *
     * @response 201 {"message": "Cita creada con éxito"}
     * @response 422 {"errors": {"fecha_cita": ["The fecha cita field is required."]}}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function storeDoctor(Request $request)
    {
        \Log::info('Intentando crear cita por doctor', ['request' => $request->all()]);

        $rules = [
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|string',
            'lugar' => 'required|string|max:255',
            'id_paciente' => 'required|exists:pacientes,id',
        ];

        $user = auth()->user();
        if ($user->id_rol === 3) { // Administrador
            $rules['id_doctor'] = 'required|exists:doctores,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            \Log::error('Validación fallida en storeDoctor', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $id_doctor = $user->id_rol === 3 ? $request->id_doctor : $user->doctor->id;

        $dia = date('w', strtotime($request->fecha_cita));

        DB::transaction(function () use ($request, $dia, $id_doctor) {
            $slot = DoctorHorario::where('id_doctor', $id_doctor)
                ->where('dia', $dia)
                ->where('hora_inicio', '<=', $request->hora_cita)
                ->where('hora_fin', '>=', $request->hora_cita)
                ->where('status', 'available')
                ->where('disponible', true)
                ->first();

            if (!$slot) {
                \Log::warning('Slot no disponible en storeDoctor', ['doctor' => $id_doctor, 'dia' => $dia, 'hora' => $request->hora_cita]);
                throw new \Exception('Slot no disponible');
            }

            $slot->bookSlot();
            \Log::info('Slot reservado en storeDoctor', ['slot_id' => $slot->id]);

            $cita = Citas::create([
                'id_paciente' => $request->id_paciente,
                'id_doctor' => $id_doctor,
                'fecha_cita' => $request->fecha_cita,
                'hora_cita' => $request->hora_cita,
                'lugar' => $request->lugar,
                'motivo' => $request->motivo ?? null,
            ]);

            \Log::info('Cita creada por doctor', ['cita_id' => $cita->id]);
        });

        return response()->json(['message' => 'Cita creada con éxito'], 201);
    }

    /**
     * @group Citas
     * @subgroup Doctor
     *
     * Update my appointment
     *
     * Allows the authenticated doctor to update their own appointment, automatically handling slot release and reservation. Cannot change the assigned doctor.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     * @bodyParam fecha_cita date required The appointment date (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required The appointment time (HH:MM). Example: 14:30
     * @bodyParam lugar string required The appointment location. Example: Consultorio 101
     * @bodyParam motivo string optional The reason for the appointment. Example: Dolor de estómago
     *
     * @response 200 {
     *   "id": 1,
     *   "id_paciente": 3,
     *   "id_doctor": 2,
     *   "fecha_cita": "2025-10-01",
     *   "hora_cita": "14:30:00",
     *   "lugar": "Consultorio 101",
     *   "motivo": "Dolor de estómago",
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Cita no encontrada o no autorizada"}
     * @response 422 {"errors": {"fecha_cita": ["The fecha cita field is required."]}}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function updateDoctor(Request $request, $id)
    {
        \Log::info('Intentando actualizar cita por doctor', ['id' => $id, 'user_id' => auth()->id(), 'request' => $request->all()]);

        $user = auth()->user();
        $query = Citas::where('id', $id);

        if ($user->id_rol !== 3) { // No es administrador
            $query->where('id_doctor', $user->doctor->id);
        }

        $cita = $query->first();

        if (!$cita) {
            \Log::warning('Cita no encontrada o no autorizada en updateDoctor', ['id' => $id, 'user_id' => auth()->id()]);
            return response()->json(['message' => 'Cita no encontrada o no autorizada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
            'motivo' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('Validación fallida en updateDoctor', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $cita) {
                $oldFecha = $cita->fecha_cita;
                $oldHora = $cita->hora_cita;
                $oldDoctorId = $cita->id_doctor;

                $newFecha = $request->fecha_cita;
                $newHora = $request->hora_cita;

                $slotChanged = ($oldFecha != $newFecha) || ($oldHora != $newHora);

                if ($slotChanged) {
                    $oldDia = date('w', strtotime($oldFecha));
                    if ($oldDia == 0) $oldDia = 7;
                    \Log::info('Liberando slot antiguo en updateDoctor', ['oldDoctorId' => $oldDoctorId, 'oldDia' => $oldDia, 'oldHora' => $oldHora]);
                    $oldSlot = DoctorHorario::where('id_doctor', $oldDoctorId)
                        ->where('dia', $oldDia)
                        ->where('hora_inicio', '<=', $oldHora)
                        ->where('hora_fin', '>=', $oldHora)
                        ->first();

                    if ($oldSlot) {
                        $oldSlot->releaseSlot();
                        \Log::info('Slot antiguo liberado en updateDoctor', ['slot_id' => $oldSlot->id]);
                    } else {
                        \Log::warning('Slot antiguo no encontrado para liberar en updateDoctor', ['oldDoctorId' => $oldDoctorId, 'oldDia' => $oldDia, 'oldHora' => $oldHora]);
                    }

                    $newDia = date('w', strtotime($newFecha));
                    if ($newDia == 0) $newDia = 7;
                    \Log::info('Buscando nuevo slot en updateDoctor', ['newDoctorId' => $oldDoctorId, 'newDia' => $newDia, 'newHora' => $newHora]);
                    $newSlot = DoctorHorario::where('id_doctor', $oldDoctorId)
                        ->where('dia', $newDia)
                        ->where('hora_inicio', '<=', $newHora)
                        ->where('hora_fin', '>=', $newHora)
                        ->where('status', 'available')
                        ->where('disponible', true)
                        ->first();

                    \Log::info('Resultado búsqueda nuevo slot en updateDoctor', ['found' => $newSlot ? true : false, 'slot_id' => $newSlot ? $newSlot->id : null]);

                    if (!$newSlot) {
                        $allSlots = DoctorHorario::where('id_doctor', $oldDoctorId)
                            ->where('dia', $newDia)
                            ->get();
                        \Log::info('Todos los slots para doctor y dia en updateDoctor', ['doctor' => $oldDoctorId, 'dia' => $newDia, 'slots' => $allSlots->toArray()]);
                        throw new \Exception('El nuevo horario no está disponible');
                    }

                    $newSlot->bookSlot();
                    \Log::info('Nuevo slot reservado en updateDoctor', ['slot_id' => $newSlot->id]);
                }

                $oldData = $cita->toArray();
                $updateData = $request->only(['fecha_cita', 'hora_cita', 'lugar', 'motivo']);
                $cita->update($updateData);
                $newData = $cita->fresh()->toArray();

                \Log::info('Cita actualizada por doctor exitosamente', ['old' => $oldData, 'new' => $newData]);
            });

            return response()->json($cita->fresh());
        } catch (\Exception $e) {
            \Log::error('Error al actualizar cita por doctor', ['error' => $e->getMessage(), 'cita_id' => $cita->id]);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @group Citas
     * @subgroup Doctor
     *
     * Delete my appointment
     *
     * Allows the authenticated doctor to delete their own appointment and release the associated time slot.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     *
     * @response 200 {"message": "Cita eliminada con éxito"}
     * @response 404 {"message": "Cita no encontrada o no autorizada"}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function destroyDoctor($id)
    {
        $user = auth()->user();
        $query = Citas::where('id', $id);

        if ($user->id_rol !== 3) { // No es administrador
            $query->where('id_doctor', $user->doctor->id);
        }

        $cita = $query->first();

        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada o no autorizada'], 404);
        }

        DB::transaction(function () use ($cita) {
            $dia = date('w', strtotime($cita->fecha_cita));
            $slot = DoctorHorario::where('id_doctor', $cita->id_doctor)
                ->where('dia', $dia)
                ->where('hora_inicio', '<=', $cita->hora_cita)
                ->where('hora_fin', '>=', $cita->hora_cita)
                ->first();

            if ($slot) {
                $slot->releaseSlot();
                \Log::info('Slot liberado al eliminar cita por doctor', ['slot_id' => $slot->id]);
            }

            $cita->delete();
            \Log::info('Cita eliminada por doctor', ['cita_id' => $cita->id]);
        });

        return response()->json(['message' => 'Cita eliminada con éxito']);
    }
    /**
     * @group Citas
     * @subgroup Doctor
     *
     * Get available patients for doctor
     *
     * Retrieves a list of all patients available for assignment to appointments.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "nombres": "Juan",
     *     "apellidos": "Pérez",
     *     "documento": "12345678",
     *     "telefono": "3001234567"
     *   }
     * ]
     * @response 500 {"message": "Error interno del servidor"}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function getPacientesDoctor()
    {
        try {
            $pacientes = Pacientes::select('id', 'nombres', 'apellidos', 'documento', 'telefono')->get();
            return response()->json($pacientes);
        } catch (\Exception $e) {
            \Log::error('Error obteniendo pacientes para doctor', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }
}
