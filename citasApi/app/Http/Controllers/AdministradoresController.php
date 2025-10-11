<?php

namespace App\Http\Controllers;

use App\Models\Administradores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdministradoresController extends Controller
{
    /**
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * List all administrators
     *
     * Retrieves a list of all registered administrators in the system.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "nombres": "Carlos",
     *     "apellidos": "Pérez",
     *     "cedula": "12345678",
     *     "telefono": "3001234567",
     *     "user_id": 5,
     *     "created_at": "2025-09-01T10:00:00.000000Z",
     *     "updated_at": "2025-09-01T10:00:00.000000Z"
     *   }
     * ]
     * @response 401 {"message": "Unauthenticated."}
     */
    public function index()
    {
        $administradores = Administradores::all();
        return response()->json($administradores);
    }

    /**
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Get administrator details
     *
     * Retrieves the details of a specific administrator by their ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the administrator. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "nombres": "Carlos",
     *   "apellidos": "Pérez",
     *   "cedula": "12345678",
     *   "telefono": "3001234567",
     *   "user_id": 5,
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Administrador no encontrado"}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Update administrator
     *
     * Allows updating the details of a specific administrator.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the administrator. Example: 1
     * @bodyParam nombres string optional First names of the administrator. Example: Carlos
     * @bodyParam apellidos string optional Last names of the administrator. Example: Pérez
     * @bodyParam cedula string optional ID card number of the administrator. Example: 12345678
     * @bodyParam telefono string optional Phone number of the administrator. Example: 3001234567
     *
     * @response 200 {
     *   "id": 1,
     *   "nombres": "Carlos",
     *   "apellidos": "Pérez",
     *   "cedula": "12345678",
     *   "telefono": "3001234567",
     *   "user_id": 5,
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Administrador no encontrado"}
     * @response 422 {"errors": {"cedula": ["The cedula has already been taken."]}}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Gestión de Usuarios
     * @subgroup Administratorsistrators
     *
     * Delete administrator
     *
     * Allows deleting an administrator from the system.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the administrator. Example: 1
     *
     * @response 200 {"message": "Administrador eliminado con éxito"}
     * @response 404 {"message": "Administrador no encontrado"}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Get my profile
     *
     * Retrieves the profile information of the authenticated administrator.
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "nombres": "Carlos",
     *   "apellidos": "Pérez",
     *   "cedula": "12345678",
     *   "telefono": "3001234567",
     *   "user_id": 5,
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Administrador no encontrado"}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Update my profile
     *
     * Allows the authenticated administrator to update their own profile information.
     *
     * @authenticated
     *
     * @bodyParam nombres string optional First names of the administrator. Example: Carlos
     * @bodyParam apellidos string optional Last names of the administrator. Example: Pérez
     * @bodyParam cedula string optional ID card number of the administrator. Example: 12345678
     * @bodyParam telefono string optional Phone number of the administrator. Example: 3001234567
     *
     * @response 200 {
     *   "id": 1,
     *   "nombres": "Carlos",
     *   "apellidos": "Pérez",
     *   "cedula": "12345678",
     *   "telefono": "3001234567",
     *   "user_id": 5,
     *   "created_at": "2025-09-01T10:00:00.000000Z",
     *   "updated_at": "2025-09-01T10:00:00.000000Z"
     * }
     * @response 404 {"message": "Administrador no encontrado"}
     * @response 422 {"errors": {"cedula": ["The cedula has already been taken."]}}
     * @response 401 {"message": "Unauthenticated."}
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
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Count administrators
     *
     * Returns the total number of registered administrators in the system.
     *
     * @authenticated
     *
     * @response 200 {"total": 5}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function count()
    {
        $total = Administradores::count();
        return response()->json(['total' => $total]);
    }

    /**
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Get support administrators
     *
     * Retrieves a list of administrators with their names and emails for support purposes.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "nombres": "Carlos",
     *     "apellidos": "Pérez",
     *     "email": "carlos@example.com"
     *   }
     * ]
     * @response 401 {"message": "Unauthenticated."}
     */
    public function support()
    {
        $admins = Administradores::with('user:id,email')->get(['id', 'nombres', 'apellidos', 'user_id']);

        $result = $admins->map(function($admin) {
            return [
                'id' => $admin->id,
                'nombres' => $admin->nombres,
                'apellidos' => $admin->apellidos,
                'email' => $admin->user->email,
            ];
        });

        return response()->json($result);
    }

    /**
     * @group Gestión de Usuarios
     * @subgroup Administrators
     *
     * Get support admins
     *
     * Retrieves a list of administrators available for support.
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "nombres": "Carlos",
     *     "apellidos": "Pérez",
     *     "email": "carlos@example.com"
     *   }
     * ]
     */
    public function getSupportAdmins()
    {
        $admins = Administradores::with('user:id,email')->get(['id', 'nombres', 'apellidos', 'user_id']);

        $result = $admins->map(function($admin) {
            return [
                'id' => $admin->id,
                'nombres' => $admin->nombres,
                'apellidos' => $admin->apellidos,
                'email' => $admin->user->email,
            ];
        });

        return response()->json($result);
    }

}
