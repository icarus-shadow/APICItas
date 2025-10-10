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
     * Listar notificaciones activas (pendientes) con paginación y filtros
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
     * Listar historial de notificaciones (aprobadas/rechazadas) con paginación y filtros
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
     * Aprobar notificación y apartar slots
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
     * Rechazar notificación
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
     * Eliminar historial
     */
    public function eliminarHistorial()
    {
        Notificaciones::whereIn('estado', [EstadoNotificacion::APROBADA, EstadoNotificacion::RECHAZADA])->delete();

        return response()->json(['message' => 'Historial eliminado']);
    }

    /**
     * Retornar contadores
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
     * Crear solicitud de notificación (para doctores)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_solicitada' => 'required|date',
            'slots' => 'required|array',
            'slots.*.fecha' => 'required|date',
            'slots.*.hora' => 'required|date_format:H:i',
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
     * Ver mis notificaciones (para doctores)
     */
    public function myNotifications()
    {
        $notificaciones = Notificaciones::where('doctor_id', auth()->user()->doctor->id)->with('administrador')->get();

        return response()->json($notificaciones);
    }

    /**
     * Actualizar notificación (aprobar/rechazar)
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
