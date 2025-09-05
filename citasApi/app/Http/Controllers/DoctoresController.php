<?php

namespace App\Http\Controllers;

use App\Models\Doctores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctoresController extends Controller
{
    /**
     * @group Doctores [ADMIN]
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
        $doctores = Doctores::all();
        return response()->json($doctores);
    }

    /**
     * @group Doctores [ADMIN]
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
     * @group Doctores [ADMIN]
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
            'telefono' => 'string|max:255',
            'especialidad_id' => 'integer|exists:especialidades,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor->update($validator->validated());

        return response()->json($doctor);
    }

    /**
     * @group Doctores [ADMIN]
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
     * @group Doctores [AUTENTICADO]
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
        $doctor = Doctores::where('user_id', auth()->id())->first();
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        return response()->json($doctor);
    }

    /**
     * @group Doctores [AUTENTICADO]
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
     * @group Doctores [AUTENTICADO]
     *
     * Eliminar mi cuenta (Doctor)
     *
     * Permite al doctor autenticado eliminar su cuenta del sistema.
     *
     * @authenticated
     *
     * @response 200 {
     *    "message": "Doctor eliminado con éxito"
     * }
     * @response 404 {
     *    "message": "Doctor no encontrado"
     * }
     */
    public function destroyOwn()
    {
        $doctor = Doctores::where('user_id', auth()->id())->first();
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        $doctor->delete();
        return response()->json(['message' => 'Doctor eliminado con éxito']);
    }
}
