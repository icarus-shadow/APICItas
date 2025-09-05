<?php

namespace App\Http\Controllers;

use App\Models\Especialidades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EspecialidadesController extends Controller
{
    /**
     * @group Especialidades [PUBLICO AUTENTICADO]
     *
     * Listar todas las especialidades
     *
     * Devuelve una lista de todas las especialidades registradas en el sistema.
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
     * @group Especialidades [PUBLICO AUTENTICADO]
     *
     * Ver una especialidad
     *
     * Devuelve los detalles de una especialidad específica.
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
     * @group Especialidades [ADMIN]
     *
     * Crear especialidad
     *
     * Permite al administrador registrar una nueva especialidad.
     *
     * @authenticated
     *
     * @bodyParam nombre string Nombre de la especialidad. Example: Cardiología
     * @bodyParam descripcion string Descripción de la especialidad. Example: Tratamiento del corazón
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
     * @group Especialidades [ADMIN]
     *
     * Actualizar especialidad
     *
     * Permite al administrador actualizar los datos de una especialidad.
     *
     * @authenticated
     *
     * @urlParam id integer ID de la especialidad. Example: 1
     * @bodyParam nombre string Nombre de la especialidad. Example: Cardiología
     * @bodyParam descripcion string Descripción de la especialidad. Example: Tratamiento del corazón
     *
     * @response 200 {
     *    "id": 1,
     *    "nombre": "Cardiología",
     *    "descripcion": "Especialidad actualizada"
     * }
     * @response 404 {
     *    "message": "Especialidad no encontrada"
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
     * @group Especialidades [ADMIN]
     *
     * Eliminar especialidad
     *
     * Permite al administrador eliminar una especialidad del sistema.
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
}
