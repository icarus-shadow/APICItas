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
     * @group Citas
     * @subgroup Administrador
     *
     * Listar todas las citas
     *
     * Obtiene una lista de todas las citas registradas en el sistema. Este endpoint está restringido a administradores.
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
     * @response 401 {"message": "No autenticado."}
     */
    public function index()
    {
        $citas = Citas::all();
        return response()->json($citas);
    }

    /**
     * @group Citas
     * @subgroup Admin
     *
     * Get appointment details
     *
     * Retrieves the details of a specific appointment by its ID. This endpoint is restricted to administrators.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "id_paciente": 3,
     *   "id_doctor": 2,
     *   "fecha_cita": "2025-10-01",
     *   "hora_cita": "14:30:00",
     *   "lugar": "Consultorio 101",
     *   "motivo": "Consulta general",
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Cita no encontrada"}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Citas
     * @subgroup Patient
     *
     * Create appointment
     *
     * Creates a new appointment for the authenticated patient in an available time slot for the selected doctor. Automatically reserves the slot.
     *
     * @authenticated
     *
     * @bodyParam id_paciente integer required The ID of the patient. Example: 3
     * @bodyParam id_doctor integer required The ID of the doctor. Example: 2
     * @bodyParam fecha_cita date required The appointment date (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required The appointment time (HH:MM). Example: 14:30
     * @bodyParam lugar string required The appointment location. Example: Consultorio 101
     * @bodyParam motivo string required The reason for the appointment. Example: Dolor de cabeza
     *
     * @response 201 {"message": "Cita creada con éxito"}
     * @response 422 {"errors": {"fecha_cita": ["The fecha cita field is required."]}}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Citas
     * @subgroup Patient
     *
     * List my appointments
     *
     * Retrieves all appointments for the authenticated patient.
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
    public function listOwn()
    {
        $citas = Citas::where('id_paciente', auth()->user()->paciente->id)->get();
        return response()->json($citas);
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
        $citas = Citas::where('id_doctor', auth()->user()->doctor->id)->get();
        return response()->json($citas);
    }

    /**
     * @group Citas
     * @subgroup Patient
     *
     * Update my appointment
     *
     * Allows the authenticated patient to update their own appointment, automatically handling slot release and reservation.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     * @bodyParam fecha_cita date required The appointment date (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required The appointment time (HH:MM). Example: 14:30
     * @bodyParam lugar string required The appointment location. Example: Consultorio 101
     * @bodyParam motivo string required The reason for the appointment. Example: Dolor de estómago
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
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('Validación fallida en updateOwn', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $cita) {
                $oldFecha = $cita->fecha_cita;
                $oldHora = $cita->hora_cita;
                $doctorId = $cita->id_doctor;

                $newFecha = $request->fecha_cita;
                $newHora = $request->hora_cita;

                // Check if slot details changed
                $slotChanged = ($oldFecha != $newFecha) || ($oldHora != $newHora);

                if ($slotChanged) {
                    // Release old slot
                    $oldDia = date('w', strtotime($oldFecha));
                    $oldSlot = DoctorHorario::where('id_doctor', $doctorId)
                        ->where('dia', $oldDia)
                        ->where('hora_inicio', '<=', $oldHora)
                        ->where('hora_fin', '>=', $oldHora)
                        ->first();

                    if ($oldSlot) {
                        $oldSlot->releaseSlot();
                        \Log::info('Slot antiguo liberado en updateOwn', ['slot_id' => $oldSlot->id]);
                    }

                    // Check if new slot is available
                    $newDia = date('w', strtotime($newFecha));
                    $newSlot = DoctorHorario::where('id_doctor', $doctorId)
                        ->where('dia', $newDia)
                        ->where('hora_inicio', '<=', $newHora)
                        ->where('hora_fin', '>=', $newHora)
                        ->where('status', 'available')
                        ->where('disponible', true)
                        ->first();

                    if (!$newSlot) {
                        throw new \Exception('El nuevo horario no está disponible');
                    }

                    // Reserve new slot
                    $newSlot->bookSlot();
                    \Log::info('Nuevo slot reservado en updateOwn', ['slot_id' => $newSlot->id]);
                }

                // Update cita
                $oldData = $cita->toArray();
                $cita->update($request->only(['fecha_cita', 'hora_cita', 'lugar', 'motivo']));
                $newData = $cita->fresh()->toArray();

                \Log::info('Cita propia actualizada exitosamente', ['old' => $oldData, 'new' => $newData]);
            });

            return response()->json($cita->fresh());
        } catch (\Exception $e) {
            \Log::error('Error al actualizar cita propia', ['error' => $e->getMessage(), 'cita_id' => $cita->id]);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @group Citas
     * @subgroup Patient
     *
     * Delete my appointment
     *
     * Allows the authenticated patient to delete their own appointment and release the associated time slot.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     *
     * @response 200 {"message": "Cita eliminada con éxito"}
     * @response 404 {"message": "Cita no encontrada o no autorizada"}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Citas
     * @subgroup Admin
     *
     * Update any appointment
     *
     * Allows the administrator to modify any appointment, automatically handling slot release and reservation.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     * @bodyParam id_paciente integer required The ID of the patient. Example: 3
     * @bodyParam id_doctor integer required The ID of the doctor. Example: 2
     * @bodyParam fecha_cita date required The appointment date (YYYY-MM-DD). Example: 2025-10-01
     * @bodyParam hora_cita time required The appointment time (HH:MM). Example: 14:30
     * @bodyParam lugar string required The appointment location. Example: Consultorio 101
     *
     * @response 200 {
     *   "id": 1,
     *   "id_paciente": 3,
     *   "id_doctor": 2,
     *   "fecha_cita": "2025-10-01",
     *   "hora_cita": "14:30:00",
     *   "lugar": "Consultorio 101",
     *   "motivo": "Consulta general",
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Cita no encontrada"}
     * @response 422 {"errors": {"id_paciente": ["The id paciente field is required."]}}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function update(Request $request, $id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_paciente' => 'required|exists:pacientes,id',
            'id_doctor' => 'required|exists:doctores,id',
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|date_format:H:i',
            'lugar' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $cita) {
                $oldDoctorId = $cita->id_doctor;
                $oldFecha = $cita->fecha_cita;
                $oldHora = $cita->hora_cita;

                $newDoctorId = $request->id_doctor;
                $newFecha = $request->fecha_cita;
                $newHora = $request->hora_cita;

                // Check if slot details changed
                $slotChanged = ($oldDoctorId != $newDoctorId) || ($oldFecha != $newFecha) || ($oldHora != $newHora);

                if ($slotChanged) {
                    // Release old slot
                    $oldDia = date('w', strtotime($oldFecha));
                    $oldSlot = DoctorHorario::where('id_doctor', $oldDoctorId)
                        ->where('dia', $oldDia)
                        ->where('hora_inicio', '<=', $oldHora)
                        ->where('hora_fin', '>=', $oldHora)
                        ->first();

                    if ($oldSlot) {
                        $oldSlot->releaseSlot();
                        \Log::info('Slot antiguo liberado', ['slot_id' => $oldSlot->id]);
                    }

                    // Check if new slot is available
                    $newDia = date('w', strtotime($newFecha));
                    $newSlot = DoctorHorario::where('id_doctor', $newDoctorId)
                        ->where('dia', $newDia)
                        ->where('hora_inicio', '<=', $newHora)
                        ->where('hora_fin', '>=', $newHora)
                        ->where('status', 'available')
                        ->where('disponible', true)
                        ->first();

                    if (!$newSlot) {
                        throw new \Exception('El nuevo horario no está disponible');
                    }

                    // Reserve new slot
                    $newSlot->bookSlot();
                    \Log::info('Nuevo slot reservado', ['slot_id' => $newSlot->id]);
                }

                // Update cita
                                $cita->update($request->only(['id_paciente', 'id_doctor', 'fecha_cita', 'hora_cita', 'lugar']));
                \Log::info('Cita actualizada', ['cita_id' => $cita->id]);
            });

            return response()->json($cita->fresh());
        } catch (\Exception $e) {
            \Log::error('Error al actualizar cita', ['error' => $e->getMessage(), 'cita_id' => $cita->id]);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @group Citas
     * @subgroup Admin
     *
     * Delete an appointment
     *
     * Allows the administrator to delete an appointment and release the associated time slot.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the appointment. Example: 1
     *
     * @response 200 {"message": "Cita eliminada con éxito"}
     * @response 404 {"message": "Cita no encontrada"}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function destroy($id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
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
                \Log::info('Slot liberado al eliminar cita', ['slot_id' => $slot->id]);
            }

            $cita->delete();
            \Log::info('Cita eliminada', ['cita_id' => $cita->id]);
        });

        return response()->json(['message' => 'Cita eliminada con éxito']);
    }

    /**
     * @group Citas
     * @subgroup Admin
     *
     * Count appointments
     *
     * Returns the total number of appointments registered in the system.
     *
     * @authenticated
     *
     * @response 200 {"total": 150}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function countCitas()
    {
        $total = Citas::count();
        return response()->json(['total' => $total]);
    }

    /**
     * @group Citas
     * @subgroup General
     *
     * Get available slots for a doctor in a date range
     *
     * Returns the available time slots for scheduling appointments for a specific doctor within a given date range.
     *
     * @urlParam doctorId integer required The ID of the doctor. Example: 1
     * @queryParam startDate date required The start date (YYYY-MM-DD). Example: 2025-10-10
     * @queryParam endDate date required The end date (YYYY-MM-DD). Example: 2025-10-10
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "fecha": "2025-10-10",
     *     "hora_inicio": "09:00",
     *     "hora_fin": "10:00",
     *     "disponible": true
     *   }
     * ]
     * @response 404 {"message": "Doctor no encontrado"}
     * @response 400 {"message": "Fechas requeridas"}
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
     * @group Citas
     * @subgroup General
     *
     * Validate if a slot is available
     *
     * Checks in real-time if a specific time slot is available for a doctor on a given date and time.
     *
     * @urlParam doctorId integer required The ID of the doctor. Example: 1
     * @bodyParam fecha date required The date (YYYY-MM-DD). Example: 2025-10-10
     * @bodyParam hora time required The time (HH:MM). Example: 09:00
     *
     * @response 200 {"available": true}
     * @response 404 {"message": "Doctor no encontrado"}
     * @response 422 {"errors": {"fecha": ["The fecha field is required."]}}
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
