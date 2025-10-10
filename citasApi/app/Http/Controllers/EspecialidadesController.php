<?php

namespace App\Http\Controllers;

use App\Models\Especialidades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EspecialidadesController extends Controller
{
    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Listar todas las especialidades
     *
     * Devuelve una lista completa de todas las especialidades médicas registradas en el sistema.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "nombre": "Cardiología",
     *      "descripcion": "Especialidad del corazón"
     *   }
     * ]
     */
    public function index()
    {
        $especialidades = Especialidades::all();
        return response()->json($especialidades);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Ver una especialidad
     *
     * Devuelve los detalles completos de una especialidad médica específica por su ID.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la especialidad. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombre": "Cardiología",
     *    "descripcion": "Especialidad del corazón"
     * }
     * @response 404 {
     *    "message": "Especialidad no encontrada"
     * }
     */
    public function show($id)
    {
        $especialidad = Especialidades::find($id);
        if (!$especialidad) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }
        return response()->json($especialidad);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Crear especialidad
     *
     * Permite a un administrador registrar una nueva especialidad médica en el sistema.
     *
     * @authenticated
     *
     * @bodyParam nombre string required Nombre de la especialidad. Example: Cardiología
     * @bodyParam descripcion string required Descripción de la especialidad. Example: Tratamiento del corazón
     *
     * @response 201 {
     *    "id": 1,
     *    "nombre": "Cardiología",
     *    "descripcion": "Especialidad del corazón"
     * }
     * @response 422 {
     *    "errors": {
     *       "nombre": ["El campo nombre es obligatorio"]
     *    }
     * }
     * @response 500 {
     *    "message": "Error interno del servidor"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $especialidad = Especialidades::create($validator->validated());
        return response()->json($especialidad, 201);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Actualizar especialidad
     *
     * Permite a un administrador actualizar los datos de una especialidad médica existente.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la especialidad. Example: 1
     * @bodyParam nombre string Nombre de la especialidad (opcional). Example: Cardiología
     * @bodyParam descripcion string Descripción de la especialidad (opcional). Example: Tratamiento del corazón
     *
     * @response 200 {
     *    "id": 1,
     *    "nombre": "Cardiología",
     *    "descripcion": "Especialidad actualizada"
     * }
     * @response 404 {
     *    "message": "Especialidad no encontrada"
     * }
     * @response 422 {
     *    "errors": {
     *       "nombre": ["El campo nombre debe ser una cadena"]
     *    }
     * }
     */
    public function update(Request $request, $id)
    {
        $especialidad = Especialidades::find($id);
        if (!$especialidad) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'descripcion' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $especialidad->update($validator->validated());

        return response()->json($especialidad);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Eliminar especialidad
     *
     * Permite a un administrador eliminar una especialidad médica del sistema de forma permanente.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la especialidad. Example: 1
     *
     * @response 200 {
     *    "message": "Especialidad eliminada con éxito"
     * }
     * @response 404 {
     *    "message": "Especialidad no encontrada"
     * }
     * @response 500 {
     *    "message": "Error interno del servidor"
     * }
     */
    public function destroy($id)
    {
        $especialidad = Especialidades::find($id);
        if (!$especialidad) {
            return response()->json(['message' => 'Especialidad no encontrada'], 404);
        }
        $especialidad->delete();
        return response()->json(['message' => 'Especialidad eliminada con éxito']);
    }

    /**
     * @group Gestión del Sistema
     * @subgroup Especialidades
     *
     * Contar especialidades
     *
     * Devuelve el número total de especialidades médicas registradas en el sistema.
     *
     * @authenticated
     *
     * @response 200 {
     *    "total": 10
     * }
     */
    public function count()
    {
        $total = Especialidades::count();
        return response()->json(['total' => $total]);
    }
}
