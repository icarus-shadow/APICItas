<?php

namespace App\Http\Controllers;

use App\Models\Horarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HorariosController extends Controller
{
    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Listar todas las plantillas de horario
     *
     * Devuelve todas las plantillas de horario disponibles para administradores.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "nombre": "Horario Mañana",
     *      "hora_inicio": "08:00",
     *      "hora_fin": "12:00",
     *      "dias": [1,3,5]
     *   }
     * ]
     */
    public function index()
    {
        $horarios = Horarios::all();

        return response()->json($horarios);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Listar todos los horarios de doctores (compacto)
     *
     * Devuelve todos los horarios asignados a doctores en formato compacto para administradores.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30",
     *      "id_doctor": 1,
     *      "disponible": true
     *   }
     * ]
     */
    public function indexCompact()
    {
        return $this->index();
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Listar horarios por doctor
     *
     * Devuelve todos los intervalos de 30 minutos asignados a un doctor específico, opcionalmente filtrados por plantilla de horario.
     *
     * @authenticated
     *
     * @urlParam id_doctor integer ID del doctor. Example: 1
     * @queryParam id_horario integer ID de la plantilla de horario (opcional). Example: 1
     *
     * @response 200 [
     *   {
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30",
     *      "disponible": true
     *   }
     * ]
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function listByDoctor(Request $request, $id_doctor)
    {
        $query = \App\Models\DoctorHorario::where('id_doctor', $id_doctor);

        if ($request->has('id_horario')) {
            $query->where('id_horario', $request->id_horario);
        }

        $horarios = $query->get();
        return response()->json($horarios);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Listar mis horarios
     *
     * Devuelve todos los intervalos de 30 minutos asignados al doctor autenticado.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30",
     *      "disponible": true
     *   }
     * ]
     * @response 403 {
     *    "message": "Acceso denegado"
     * }
     */
    public function listOwn()
    {
        $horarios = \App\Models\DoctorHorario::where('id_doctor', auth()->user()->doctor->id)->get();
        return response()->json($horarios);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Crear plantilla de horario
     *
     * Crea una nueva plantilla de horario con nombre y configuración, sin asignar a ningún doctor.
     *
     * @authenticated
     *
     * @bodyParam nombre string required Nombre de la plantilla. Example: Horario Mañana
     * @bodyParam dias array required Lista de días (1=Lunes, 7=Domingo). Example: [1,3,5]
     * @bodyParam hora_inicio string required Hora de inicio (HH:mm). Example: 08:00
     * @bodyParam hora_fin string required Hora de fin (HH:mm). Example: 12:00
     *
     * @response 201 {
     *    "id": 1,
     *    "nombre": "Horario Mañana",
     *    "hora_inicio": "08:00",
     *    "hora_fin": "12:00",
     *    "dias": [1,3,5]
     * }
     * @response 422 {
     *    "errors": {
     *       "nombre": ["El campo nombre es obligatorio"]
     *    }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'dias' => 'required|array|min:1',
            'dias.*' => 'integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $horario = Horarios::create([
            'nombre' => $data['nombre'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'dias' => $data['dias'],
        ]);

        return response()->json($horario, 201);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Asignar horario a doctor
     *
     * Asigna una plantilla de horario a un doctor, creando automáticamente los slots individuales de 30 minutos.
     *
     * @authenticated
     *
     * @bodyParam id_horario integer required ID de la plantilla de horario. Example: 1
     * @bodyParam id_doctor integer required ID del doctor. Example: 1
     *
     * @response 201 [
     *   {
     *      "id_horario": 1,
     *      "id_doctor": 1,
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30",
     *      "disponible": true
     *   }
     * ]
     * @response 422 {
     *    "error": "Conflicto de horarios detectado",
     *    "conflictos": [...],
     *    "message": "No se puede asignar este horario porque hay conflictos."
     * }
     */
    public function assignToDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_horario' => 'required|exists:horarios,id',
            'id_doctor' => 'required|exists:doctores,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $horarioTemplate = Horarios::find($request->id_horario);

        // Validar conflictos de horario
        $conflictos = $this->checkHorarioConflicts($request->id_doctor, $horarioTemplate);
        if (!empty($conflictos)) {
            return response()->json([
                'error' => 'Conflicto de horarios detectado',
                'conflictos' => $conflictos,
                'message' => 'No se puede asignar este horario porque hay conflictos con horarios ya asignados al doctor.'
            ], 422);
        }

        $hora_inicio = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_inicio);
        $hora_fin = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_fin);
        $interval = new \DateInterval('PT30M');

        $createdDoctorHorarios = [];

        foreach ($horarioTemplate->dias as $dia) {
            $period = new \DatePeriod($hora_inicio, $interval, $hora_fin);

            foreach ($period as $start) {
                $end = clone $start;
                $end->add($interval);

                $doctorHorario = \App\Models\DoctorHorario::create([
                    'id_horario' => $horarioTemplate->id,
                    'id_doctor' => $request->id_doctor,
                    'dia' => $dia,
                    'hora_inicio' => $start->format('H:i'),
                    'hora_fin' => $end->format('H:i'),
                    'disponible' => true,
                    'status' => 'available',
                ]);

                $createdDoctorHorarios[] = $doctorHorario;
            }
        }

        return response()->json($createdDoctorHorarios, 201);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Desasignar horario de doctor
     *
     * Elimina todos los slots de 30 minutos de una plantilla de horario específica asignada a un doctor.
     *
     * @authenticated
     *
     * @bodyParam id_horario integer required ID de la plantilla de horario. Example: 1
     * @bodyParam id_doctor integer required ID del doctor. Example: 1
     *
     * @response 200 {
     *    "message": "Horario desasignado correctamente"
     * }
     * @response 422 {
     *    "errors": {
     *       "id_horario": ["El campo id_horario es obligatorio"]
     *    }
     * }
     */
    public function unassignFromDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_horario' => 'required|exists:horarios,id',
            'id_doctor' => 'required|exists:doctores,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Eliminar todos los slots de este horario para este doctor
        \App\Models\DoctorHorario::where('id_horario', $request->id_horario)
            ->where('id_doctor', $request->id_doctor)
            ->delete();

        return response()->json(['message' => 'Horario desasignado correctamente']);
    }

    /**
     * Verificar conflictos de horarios para un doctor
     */
    public function checkHorarioConflicts($id_doctor, $horarioTemplate)
    {
        $conflictos = [];

        // Obtener todos los horarios ya asignados al doctor
        $existingHorarios = \App\Models\DoctorHorario::where('id_doctor', $id_doctor)->get();

        $hora_inicio_nuevo = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_inicio);
        $hora_fin_nuevo = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_fin);

        foreach ($horarioTemplate->dias as $dia) {
            // Filtrar horarios existentes para este día
            $horariosDia = $existingHorarios->where('dia', $dia);

            foreach ($horariosDia as $existingHorario) {
                $hora_inicio_existente = \DateTime::createFromFormat('H:i', $existingHorario->hora_inicio);
                $hora_fin_existente = \DateTime::createFromFormat('H:i', $existingHorario->hora_fin);

                // Verificar solapamiento
                if ($hora_inicio_nuevo < $hora_fin_existente && $hora_fin_nuevo > $hora_inicio_existente) {
                    $conflictos[] = [
                        'dia' => $dia,
                        'horario_conflictivo' => [
                            'hora_inicio' => $existingHorario->hora_inicio,
                            'hora_fin' => $existingHorario->hora_fin,
                            'horario_template' => $existingHorario->horario->nombre ?? 'Desconocido'
                        ],
                        'horario_nuevo' => [
                            'hora_inicio' => $horarioTemplate->hora_inicio,
                            'hora_fin' => $horarioTemplate->hora_fin,
                            'horario_template' => $horarioTemplate->nombre
                        ]
                    ];
                }
            }
        }

        return $conflictos;
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Verificar conflictos de horario antes de asignar
     *
     * Verifica si asignar una plantilla de horario a un doctor causaría conflictos con horarios ya existentes.
     *
     * @authenticated
     *
     * @bodyParam id_doctor integer required ID del doctor. Example: 1
     * @bodyParam id_horario integer required ID de la plantilla de horario. Example: 1
     *
     * @response 200 {
     *    "conflictos": []
     * }
     * @response 422 {
     *    "conflictos": [
     *       {
     *          "dia": 1,
     *          "horario_conflictivo": {...},
     *          "horario_nuevo": {...}
     *       }
     *    ]
     * }
     */
    public function checkConflict(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_doctor' => 'required|exists:doctores,id',
            'id_horario' => 'required|exists:horarios,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $horarioTemplate = Horarios::find($request->id_horario);
        $conflictos = $this->checkHorarioConflicts($request->id_doctor, $horarioTemplate);

        return response()->json(['conflictos' => $conflictos]);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Ver un horario
     *
     * Devuelve la información detallada de una plantilla de horario específica.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombre": "Horario Mañana",
     *    "hora_inicio": "08:00",
     *    "hora_fin": "12:00",
     *    "dias": [1,3,5]
     * }
     * @response 404 {
     *    "message": "Horario no encontrado"
     * }
     */
    public function show($id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        return response()->json($horario);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Editar una plantilla de horario
     *
     * Permite editar los datos de una plantilla de horario existente.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     * @bodyParam nombre string Nombre de la plantilla (opcional). Example: Horario Mañana
     * @bodyParam dias array Lista de días (opcional). Example: [1,3,5]
     * @bodyParam hora_inicio string Hora de inicio (opcional). Example: 09:00
     * @bodyParam hora_fin string Hora de fin (opcional). Example: 13:00
     *
     * @response 200 {
     *    "id": 1,
     *    "nombre": "Horario Mañana",
     *    "hora_inicio": "09:00",
     *    "hora_fin": "13:00",
     *    "dias": [1,3,5]
     * }
     * @response 404 {
     *    "message": "Plantilla de horario no encontrada"
     * }
     * @response 422 {
     *    "errors": {
     *       "hora_inicio": ["El formato de hora no es válido"]
     *    }
     * }
     */
    public function update(Request $request, $id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Plantilla de horario no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'dias' => 'required|array|min:1',
            'dias.*' => 'integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $horario->update($validator->validated());

        return response()->json($horario);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Eliminar una plantilla de horario
     *
     * Permite eliminar una plantilla de horario del sistema.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     *
     * @response 200 {
     *    "message": "Horario eliminado con éxito"
     * }
     * @response 404 {
     *    "message": "Horario no encontrado"
     * }
     */
    public function destroy($id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        $horario->delete();
        return response()->json(['message' => 'Horario eliminado con éxito']);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Horarios
     *
     * Contar plantillas de horarios
     *
     * Devuelve el número total de plantillas de horarios registradas en el sistema.
     *
     * @authenticated
     *
     * @response 200 {
     *    "total": 3
     * }
     */
     public function count()
     {
         $total = Horarios::count();
         return response()->json(['total' => $total]);
     }

}
