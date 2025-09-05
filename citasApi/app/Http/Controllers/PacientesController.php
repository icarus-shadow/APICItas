<?php

namespace App\Http\Controllers;

use App\Models\Pacientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PacientesController extends Controller
{
    /**
     * @group Pacientes [ADMIN]
     *
     * Listar todos los pacientes
     *
     * Devuelve la lista completa de pacientes registrados en el sistema.
     *
     * @authenticated
     *
     * @response 200 [
     *  {
     *    "id": 1,
     *    "nombres": "Juan",
     *    "apellidos": "Pérez",
     *    "documento": "12345678",
     *    "telefono": "3001234567"
     *  }
     * ]
     */
    public function index()
    {
        $pacientes = Pacientes::all();
        return response()->json($pacientes);
    }

    /**
     * @group Pacientes [ADMIN]
     *
     * Ver un paciente específico
     *
     * Obtiene los datos completos de un paciente a partir de su ID.
     *
     * @authenticated
     *
     * @urlParam id integer required ID del paciente a consultar. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Juan",
     *    "apellidos": "Pérez",
     *    "documento": "12345678",
     *    "telefono": "3001234567"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     */
    public function show($id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        return response()->json($paciente);
    }

    /**
     * @group Pacientes [ADMIN]
     *
     * Actualizar un paciente
     *
     * Permite al administrador modificar los datos de un paciente específico.
     *
     * @authenticated
     *
     * @urlParam id integer required ID del paciente a actualizar. Example: 1
     * @bodyParam nombres string Nombre del paciente. Example: Juan
     * @bodyParam apellidos string Apellido del paciente. Example: Pérez
     * @bodyParam documento string Documento de identidad. Example: 12345678
     * @bodyParam rh string Grupo sanguíneo. Example: O+
     * @bodyParam fecha_nacimiento date Fecha de nacimiento. Example: 1990-05-12
     * @bodyParam genero string Género (M o F). Example: M
     * @bodyParam edad integer Edad del paciente. Example: 30
     * @bodyParam telefono string Número telefónico. Example: 3001234567
     * @bodyParam alergias string Alergias del paciente. Example: Ninguna
     * @bodyParam comentarios string Comentarios adicionales. Example: Paciente estable
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Juan",
     *    "apellidos": "Pérez",
     *    "telefono": "3001234567"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     * @response 422 {
     *    "errors": {
     *       "nombres": ["El campo nombres es obligatorio"]
     *    }
     * }
     */
    public function update(Request $request, $id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'documento' => 'string|max:255|unique:pacientes,documento,' . $id,
            'rh' => 'string|max:255',
            'fecha_nacimiento' => 'date',
            'genero' => 'in:M,F',
            'edad' => 'integer',
            'telefono' => 'string|max:255',
            'alergias' => 'nullable|string|max:255',
            'comentarios' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente->update($validator->validated());

        return response()->json($paciente);
    }

    /**
     * @group Pacientes [ADMIN]
     *
     * Eliminar un paciente
     *
     * Permite al administrador eliminar un paciente del sistema.
     *
     * @authenticated
     *
     * @urlParam id integer required ID del paciente a eliminar. Example: 1
     *
     * @response 200 {
     *    "message": "Paciente eliminado con éxito"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     */
    public function destroy($id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        $paciente->delete();
        return response()->json(['message' => 'Paciente eliminado con éxito']);
    }

    /**
     * @group Pacientes [AUTENTICADO]
     *
     * Ver mi información
     *
     * Devuelve los datos del paciente autenticado.
     *
     * @authenticated
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Juan",
     *    "apellidos": "Pérez",
     *    "documento": "12345678",
     *    "telefono": "3001234567"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     */
    public function showOwn()
    {
        $paciente = Pacientes::where('user_id', auth()->id())->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        return response()->json($paciente);
    }

    /**
     * @group Pacientes [AUTENTICADO]
     *
     * Actualizar mi información
     *
     * Permite al paciente autenticado actualizar sus datos personales.
     *
     * @authenticated
     *
     * @bodyParam nombres string Nombre del paciente. Example: Juan
     * @bodyParam apellidos string Apellido del paciente. Example: Pérez
     * @bodyParam telefono string Número telefónico. Example: 3001234567
     * @bodyParam alergias string Alergias conocidas. Example: Ninguna
     * @bodyParam comentarios string Comentarios adicionales. Example: Paciente estable
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Juan",
     *    "apellidos": "Pérez",
     *    "telefono": "3001234567"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     * @response 422 {
     *    "errors": {
     *       "nombres": ["El campo nombres es obligatorio"]
     *    }
     * }
     */
    public function updateOwn(Request $request)
    {
        $paciente = Pacientes::where('user_id', auth()->id())->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'telefono' => 'string|max:255',
            'alergias' => 'nullable|string|max:255',
            'comentarios' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente->update($validator->validated());

        return response()->json($paciente);
    }

    /**
     * @group Pacientes [AUTENTICADO]
     *
     * Eliminar mi cuenta
     *
     * Permite al paciente autenticado eliminar su cuenta del sistema junto con todos sus datos.
     *
     * @authenticated
     *
     * @response 200 {
     *    "message": "Paciente eliminado con éxito"
     * }
     * @response 404 {
     *    "message": "Paciente no encontrado"
     * }
     */
    public function destroyOwn()
    {
        $paciente = Pacientes::where('user_id', auth()->id())->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        $paciente->delete();
        return response()->json(['message' => 'Paciente eliminado con éxito']);
    }
}
