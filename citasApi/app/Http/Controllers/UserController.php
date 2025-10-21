<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Mostrar la información de un usuario específico por ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Buscar el usuario por ID
        $user = User::find($id);

        // Si el usuario no existe, devolver error 404
        if (!$user) {
            return response()->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        // Devolver la información del usuario en formato JSON
        return response()->json($user);
    }
}
