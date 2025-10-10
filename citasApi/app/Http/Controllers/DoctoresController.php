<?php

namespace App\Http\Controllers;

use App\Models\Doctores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctoresController extends Controller
{
    /**
     * @group Gestión de Usuarios [ADMIN]
     *
     * Listar doctores
     *
     * Devuelve la lista completa de doctores registrados en el sistema.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "nombres": "Pedro",
     *      "apellidos": "López",
     *      "especialidad": "Cardiología"
     *   }
     * ]
     */
    public function index()
    {
        $doctores = Doctores::with(['doctorHorarios.horario', 'user'])->get()->map(function ($doctor) {
            $horariosAsignados = $doctor->doctorHorarios->map(function($dh) { return $dh->horario->nombre; })->unique()->values();
            return [
                'id' => $doctor->id,
                'user_id' => $doctor->user_id,
                'cedula' => $doctor->cedula,
                'nombres' => $doctor->nombres ?: ($doctor->user ? $doctor->user->name : 'Sin nombre'),
                'apellidos' => $doctor->apellidos ?: '',
                'id_especialidades' => $doctor->id_especialidades,
                'horario' => $doctor->horario,
                'lugar_trabajo' => $doctor->lugar_trabajo,
                'created_at' => $doctor->created_at,
                'updated_at' => $doctor->updated_at,
                'horarios_asignados' => $horariosAsignados
            ];
        });
        return response()->json($doctores);
    }

    /**
     * @group Gestión de Usuarios [ADMIN]
     *
     * Ver un doctor
     *
     * Devuelve los datos de un doctor específico por su ID.
     *
     * @authenticated
     *
     * @urlParam id integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Pedro",
     *    "apellidos": "López",
     *    "especialidad": "Cardiología"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function show($id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        return response()->json($doctor);
    }

    /**
     * @group Gestión de Usuarios [ADMIN]
     *
     * Actualizar un doctor
     *
     * Permite al administrador actualizar los datos de un doctor por ID.
     *
     * @authenticated
     *
     * @bodyParam nombres string Nombre del doctor. Example: Pedro
     * @bodyParam apellidos string Apellido del doctor. Example: López
     * @bodyParam telefono string Número telefónico. Example: 3012345678
     * @bodyParam especialidad integer ID de la especialidad. Example: 1
     * @bodyParam horario integer ID del horario a asignar (opcional). Example: 1
     *
     * @urlParam id integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Pedro",
     *    "apellidos": "López"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     * @response 422 {
     *    "error": "Conflicto de horarios detectado"
     * }
     */
    public function update(Request $request, string $id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'cedula' => 'string|max:255|unique:doctores,cedula,' . $id,
            'lugar_trabajo' => 'nullable|string|max:255',
            'telefono' => 'string|max:255',
            'especialidad' => 'nullable|integer|exists:especialidades,id',
            'horarios_asignados' => 'nullable|array',
            'horarios_asignados.*.id' => 'integer|exists:horarios,id',
            'horarios_asignados.*.nombre' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Mapear nombres simplificados a nombres de base de datos
        if (isset($data['especialidad'])) {
            $data['id_especialidades'] = $data['especialidad'];
            unset($data['especialidad']);
        }

        // Nota: Los horarios se manejan directamente desde el frontend con las llamadas
        // a /asignar-horario y /desasignar-horario, por lo que aquí no necesitamos
        // procesar horarios_asignados. Solo removemos el campo si existe.
        if (isset($data['horarios_asignados'])) {
            unset($data['horarios_asignados']);
        }

        $doctor->update($data);

        return response()->json($doctor);
    }



    /**
     * @group Gestión de Usuarios [ADMIN]
     *
     * Eliminar un doctor
     *
     * Permite al administrador eliminar un doctor por ID.
     *
     * @authenticated
     *
     * @urlParam id integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "message": "Doctor eliminado con éxito"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function destroy(string $id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        $doctor->delete();
        return response()->json(['message' => 'Doctor eliminado con éxito']);
    }

    /**
     * @group Gestión de Usuarios [AUTENTICADO]
     *
     * Ver mi información (Doctor)
     *
     * Devuelve los datos del doctor autenticado.
     *
     * @authenticated
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Pedro",
     *    "apellidos": "López"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function showOwn()
    {
        $doctor = Doctores::with('especialidad')->where('user_id', auth()->id())->first();
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        $data = $doctor->toArray();
        $data['especialidades'] = $doctor->especialidad ? $doctor->especialidad->nombre : null;
        return response()->json($data);
    }

    /**
     * @group Gestión de Usuarios [AUTENTICADO]
     *
     * Actualizar mi información (Doctor)
     *
     * Permite al doctor autenticado actualizar sus propios datos.
     *
     * @authenticated
     *
     * @bodyParam nombres string Nombre del doctor. Example: Pedro
     * @bodyParam apellidos string Apellido del doctor. Example: López
     * @bodyParam telefono string Número telefónico. Example: 3012345678
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Pedro",
     *    "apellidos": "López"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     * @response 422 {
     *    "errors": {
     *       "nombres": ["El campo nombres debe ser una cadena de texto"]
     *    }
     * }
     */
    public function updateOwn(Request $request)
    {
        $doctor = Doctores::where('user_id', auth()->id())->first();
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'telefono' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor->update($validator->validated());

        return response()->json($doctor);
    }

    /**
     * @group Gestión de Usuarios [ADMIN]
     *
     * Contar doctores
     *
     * Devuelve el número total de doctores registrados en el sistema.
     *
     * @authenticated
     *
     * @response 200 {
     *    "total": 10
     * }
     */
    public function count()
    {
        $total = Doctores::count();
        return response()->json(['total' => $total]);
    }
    /**
     * @group Gestión de Doctores [DOCTOR]
     *
     * Contar citas asignadas a un doctor
     *
     * Devuelve el número total de citas asignadas a un doctor específico.
     *
     * @authenticated
     *
     * @urlParam doctorId integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "total": 5
     * }
     */
    public function countCitasAsignadas($doctorId)
    {
        $total = \App\Models\Citas::where('id_doctor', $doctorId)->count();
        return response()->json(['total' => $total]);
    }

    /**
     * @group Gestión de Doctores [DOCTOR]
     *
     * Contar citas próximas de un doctor
     *
     * Devuelve el número de citas próximas (futuras) asignadas a un doctor específico.
     *
     * @authenticated
     *
     * @urlParam doctorId integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "total": 3
     * }
     */
    public function countCitasProximas($doctorId)
    {
        $total = \App\Models\Citas::where('id_doctor', $doctorId)
            ->whereRaw("CONCAT(fecha_cita, ' ', hora_cita) > NOW()")
            ->count();
        return response()->json(['total' => $total]);
    }

    /**
     * @group Gestión de Doctores [DOCTOR]
     *
     * Contar pacientes atendidos por un doctor
     *
     * Devuelve el número de pacientes únicos atendidos (citas pasadas) por un doctor específico.
     *
     * @authenticated
     *
     * @urlParam doctorId integer ID del doctor. Example: 1
     *
     * @response 200 {
     *    "total": 10
     * }
     */
    public function countPacientesAtendidos($doctorId)
    {
        $total = \App\Models\Citas::where('id_doctor', $doctorId)
            ->whereRaw("CONCAT(fecha_cita, ' ', hora_cita) < NOW()")
            ->distinct('id_paciente')
            ->count('id_paciente');
        return response()->json(['total' => $total]);
    }

}
