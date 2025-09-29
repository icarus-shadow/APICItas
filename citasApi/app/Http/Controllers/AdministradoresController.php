<?php

namespace App\Http\Controllers;

use App\Models\Administradores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdministradoresController extends Controller
{
    /**
     * @group Administradores [ADMIN]
     *
     * Listar administradores
     *
     * Devuelve una lista de todos los administradores registrados.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id": 1,
     *      "nombres": "Carlos",
     *      "apellidos": "Pérez",
     *      "cedula": "12345678",
     *      "telefono": "3001234567",
     *      "user_id": 5
     *   }
     * ]
     */
    public function index()
    {
        $administradores = Administradores::all();
        return response()->json($administradores);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Ver administrador
     *
     * Devuelve los datos de un administrador específico.
     *
     * @authenticated
     *
     * @urlParam id integer ID del administrador. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Carlos",
     *    "apellidos": "Pérez",
     *    "cedula": "12345678",
     *    "telefono": "3001234567",
     *    "user_id": 5
     * }
     */
    public function show($id)
    {
        $admin = Administradores::find($id);
        if (!$admin) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }
        return response()->json($admin);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Actualizar administrador
     *
     * Permite modificar los datos de un administrador.
     *
     * @authenticated
     *
     * @urlParam id integer ID del administrador. Example: 1
     *
     * @bodyParam nombres string Nombres del administrador. Example: Carlos
     * @bodyParam apellidos string Apellidos del administrador. Example: Pérez
     * @bodyParam cedula string Cédula del administrador. Example: 12345678
     * @bodyParam telefono string Teléfono del administrador. Example: 3001234567
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Carlos",
     *    "apellidos": "Pérez",
     *    "cedula": "12345678",
     *    "telefono": "3001234567",
     *    "user_id": 5
     * }
     */
    public function update(Request $request, $id)
    {
        $admin = Administradores::find($id);
        if (!$admin) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'cedula' => 'string|max:20|unique:administradores,cedula,' . $id,
            'telefono' => 'string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin->update($validator->validated());

        return response()->json($admin);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Eliminar administrador
     *
     * Permite eliminar un administrador del sistema.
     *
     * @authenticated
     *
     * @urlParam id integer ID del administrador. Example: 1
     *
     * @response 200 {
     *    "message": "Administrador eliminado con éxito"
     * }
     */
    public function destroy($id)
    {
        $admin = Administradores::find($id);
        if (!$admin) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }

        $admin->delete();

        return response()->json(['message' => 'Administrador eliminado con éxito']);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Ver mi perfil
     *
     * Devuelve los datos del administrador autenticado.
     *
     * @authenticated
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Carlos",
     *    "apellidos": "Pérez",
     *    "cedula": "12345678",
     *    "telefono": "3001234567",
     *    "user_id": 5
     * }
     */
    public function showOwn()
    {
        $admin = Administradores::where('user_id', Auth::id())->first();
        if (!$admin) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }
        return response()->json($admin);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Actualizar mi perfil
     *
     * Permite al administrador autenticado modificar sus propios datos.
     *
     * @authenticated
     *
     * @bodyParam nombres string Nombres del administrador. Example: Carlos
     * @bodyParam apellidos string Apellidos del administrador. Example: Pérez
     * @bodyParam cedula string Cédula del administrador. Example: 12345678
     * @bodyParam telefono string Teléfono del administrador. Example: 3001234567
     *
     * @response 200 {
     *    "id": 1,
     *    "nombres": "Carlos",
     *    "apellidos": "Pérez",
     *    "cedula": "12345678",
     *    "telefono": "3001234567",
     *    "user_id": 5
     * }
     */
    public function updateOwn(Request $request)
    {
        $admin = Administradores::where('user_id', Auth::id())->first();
        if (!$admin) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'cedula' => 'string|max:20|unique:administradores,cedula,' . $admin->id,
            'telefono' => 'string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin->update($validator->validated());

        return response()->json($admin);
    }

    /**
     * @group Administradores [ADMIN]
     *
     * Contar administradores
     *
     * Devuelve el número total de administradores registrados.
     *
     * @authenticated
     *
     * @response 200 {
     *    "total": 5
     * }
     */
    public function count()
    {
        $total = Administradores::count();
        return response()->json(['total' => $total]);
    }

}
