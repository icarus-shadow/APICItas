<?php

namespace App\Http\Controllers;

use App\Models\Notificaciones;
use App\Models\DoctorHorario;
use App\Enums\EstadoNotificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NotificacionesController extends Controller
{
     /**
      * @group Gestión del Sistema
      * @subgroup Notificaciones
      *
      * Listar notificaciones activas (pendientes)
      *
      * Devuelve una lista paginada de notificaciones pendientes con filtros opcionales por doctor y fecha.
      *
      * @authenticated
      *
      * @queryParam doctor_id integer ID del doctor (opcional). Example: 1
      * @queryParam fecha_solicitada date Fecha solicitada (opcional). Example: 2025-10-10
      * @queryParam per_page integer Número de elementos por página (opcional, default 15). Example: 10
      *
      * @response 200 {
      *    "current_page": 1,
      *    "data": [
      *       {
      *          "id": 1,
      *          "doctor_id": 1,
      *          "fecha_solicitada": "2025-10-10",
      *          "slots": [...],
      *          "estado": "pendiente",
      *          "doctor": {...}
      *       }
      *    ],
      *    "per_page": 15,
      *    "total": 1
      * }
      */
     public function index(Request $request)
    {
        $query = Notificaciones::where('estado', EstadoNotificacion::PENDIENTE)->with('doctor');

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('fecha_solicitada')) {
            $query->where('fecha_solicitada', $request->fecha_solicitada);
        }

        $notificaciones = $query->paginate($request->get('per_page', 15));

        return response()->json($notificaciones);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Listar historial de notificaciones
     *
     * Devuelve una lista paginada del historial de notificaciones aprobadas o rechazadas con filtros opcionales.
     *
     * @authenticated
     *
     * @queryParam doctor_id integer ID del doctor (opcional). Example: 1
     * @queryParam fecha_solicitada date Fecha solicitada (opcional). Example: 2025-10-10
     * @queryParam per_page integer Número de elementos por página (opcional, default 15). Example: 10
     *
     * @response 200 {
     *    "current_page": 1,
     *    "data": [
     *       {
     *          "id": 1,
     *          "doctor_id": 1,
     *          "fecha_solicitada": "2025-10-10",
     *          "slots": [...],
     *          "estado": "aprobada",
     *          "admin_id": 1,
     *          "doctor": {...},
     *          "administrador": {...}
     *       }
     *    ],
     *    "per_page": 15,
     *    "total": 1
     * }
     */
    public function historial(Request $request)
    {
        $query = Notificaciones::whereIn('estado', [EstadoNotificacion::APROBADA, EstadoNotificacion::RECHAZADA])->with('doctor', 'administrador');

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->has('fecha_solicitada')) {
            $query->where('fecha_solicitada', $request->fecha_solicitada);
        }

        $notificaciones = $query->paginate($request->get('per_page', 15));

        return response()->json($notificaciones);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Aprobar notificación
     *
     * Aprueba una notificación pendiente y aparta los slots correspondientes en la agenda del doctor.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la notificación. Example: 1
     *
     * @response 200 {
     *    "message": "Notificación aprobada y slots apartados"
     * }
     * @response 404 {
     *    "message": "Notificación no encontrada o no pendiente"
     * }
     * @response 500 {
     *    "message": "Error interno del servidor"
     * }
     */
    public function aprobar($id)
    {
        $notificacion = Notificaciones::find($id);

        if (!$notificacion || $notificacion->estado !== EstadoNotificacion::PENDIENTE) {
            return response()->json(['message' => 'Notificación no encontrada o no pendiente'], 404);
        }

        DB::transaction(function () use ($notificacion) {
            $notificacion->estado = EstadoNotificacion::APROBADA;
            $notificacion->admin_id = auth()->user()->administrador->id;
            $notificacion->save();

            // Apartar slots
            if (!$notificacion->slots || !is_array($notificacion->slots)) {
                throw new \Exception('Los slots de la notificación están vacíos o no son válidos');
            }

            foreach ($notificacion->slots as $slot) {
                $dia = date('w', strtotime($slot['fecha']));
                $hora_start = explode('-', $slot['hora'])[0]; // Extraer hora de inicio del rango
                $doctorHorario = DoctorHorario::where('id_doctor', $notificacion->doctor->id)
                    ->where('dia', $dia)
                    ->where('hora_inicio', '<=', $hora_start)
                    ->where('hora_fin', '>', $hora_start)
                    ->where('status', 'available')
                    ->where('disponible', true)
                    ->first();

                if ($doctorHorario) {
                    $doctorHorario->bookSlot();
                }
            }
        });

        return response()->json(['message' => 'Notificación aprobada y slots apartados']);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Rechazar notificación
     *
     * Rechaza una notificación pendiente.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la notificación. Example: 1
     *
     * @response 200 {
     *    "message": "Notificación rechazada"
     * }
     * @response 404 {
     *    "message": "Notificación no encontrada o no pendiente"
     * }
     */
    public function rechazar($id)
    {
        $notificacion = Notificaciones::find($id);

        if (!$notificacion || $notificacion->estado !== EstadoNotificacion::PENDIENTE) {
            return response()->json(['message' => 'Notificación no encontrada o no pendiente'], 404);
        }

        $notificacion->estado = EstadoNotificacion::RECHAZADA;
        $notificacion->admin_id = auth()->user()->administrador->id;
        $notificacion->save();

        return response()->json(['message' => 'Notificación rechazada']);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Eliminar historial de notificaciones
     *
     * Elimina permanentemente todo el historial de notificaciones aprobadas y rechazadas.
     *
     * @authenticated
     *
     * @response 200 {
     *    "message": "Historial eliminado"
     * }
     */
    public function eliminarHistorial()
    {
        Notificaciones::whereIn('estado', [EstadoNotificacion::APROBADA, EstadoNotificacion::RECHAZADA])->delete();

        return response()->json(['message' => 'Historial eliminado']);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Contadores de notificaciones
     *
     * Devuelve los contadores de notificaciones por estado: pendientes, aprobadas y rechazadas.
     *
     * @authenticated
     *
     * @response 200 {
     *    "pendientes": 5,
     *    "aprobadas": 10,
     *    "rechazadas": 2
     * }
     */
    public function contadores()
    {
        $pendientes = Notificaciones::where('estado', EstadoNotificacion::PENDIENTE)->count();
        $aprobadas = Notificaciones::where('estado', EstadoNotificacion::APROBADA)->count();
        $rechazadas = Notificaciones::where('estado', EstadoNotificacion::RECHAZADA)->count();

        return response()->json([
            'pendientes' => $pendientes,
            'aprobadas' => $aprobadas,
            'rechazadas' => $rechazadas,
        ]);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Crear solicitud de notificación
     *
     * Permite a un doctor crear una nueva solicitud de notificación para apartar slots en su agenda.
     *
     * @authenticated
     *
     * @bodyParam fecha_solicitada date required Fecha para la que se solicita el apartado. Example: 2025-10-15
     * @bodyParam slots array required Lista de slots a apartar. Example: [{"fecha": "2025-10-15", "hora": "10:00-10:30"}]
     * @bodyParam slots.*.fecha date required Fecha del slot. Example: 2025-10-15
     * @bodyParam slots.*.hora string required Rango horario del slot. Example: 10:00-10:30
     *
     * @response 201 {
     *    "id": 1,
     *    "doctor_id": 1,
     *    "fecha_solicitada": "2025-10-15",
     *    "slots": [...],
     *    "estado": "pendiente"
     * }
     * @response 422 {
     *    "errors": {
     *       "fecha_solicitada": ["El campo fecha_solicitada es obligatorio"]
     *    }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_solicitada' => 'required|date',
            'slots' => 'required|array',
            'slots.*.fecha' => 'required|date',
            'slots.*.hora' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notificacion = Notificaciones::create([
            'doctor_id' => auth()->user()->doctor->id,
            'fecha_solicitada' => $request->fecha_solicitada,
            'slots' => $request->slots,
            'estado' => EstadoNotificacion::PENDIENTE,
        ]);

        return response()->json($notificacion, 201);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Ver mis notificaciones
     *
     * Devuelve todas las notificaciones del doctor autenticado.
     *
     * @authenticated
     *
     * @response 200 [
     *    {
     *       "id": 1,
     *       "doctor_id": 1,
     *       "fecha_solicitada": "2025-10-15",
     *       "slots": [...],
     *       "estado": "pendiente",
     *       "administrador": {...}
     *    }
     * ]
     */
    public function myNotifications()
    {
        $notificaciones = Notificaciones::where('doctor_id', auth()->user()->doctor->id)->with('administrador')->get();

        return response()->json($notificaciones);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Notificaciones
     *
     * Actualizar notificación
     *
     * Permite aprobar o rechazar una notificación pendiente.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la notificación. Example: 1
     * @bodyParam estado string required Estado a actualizar (aprobada o rechazada). Example: aprobada
     *
     * @response 200 {
     *    "message": "Notificación aprobada y slots apartados"
     * }
     * @response 404 {
     *    "message": "Notificación no encontrada"
     * }
     * @response 422 {
     *    "errors": {
     *       "estado": ["El estado debe ser aprobada o rechazada"]
     *    }
     * }
     */
    public function update(Request $request, $id)
    {
        $notificacion = Notificaciones::find($id);

        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:aprobada,rechazada',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->estado === 'aprobada') {
            return $this->aprobar($id);
        } elseif ($request->estado === 'rechazada') {
            return $this->rechazar($id);
        }
    }
}
