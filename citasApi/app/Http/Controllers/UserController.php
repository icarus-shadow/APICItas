<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
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

    /**
     * Registrar token de dispositivo para notificaciones push
     */
    public function registerDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|string|in:ios,android,web',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'device_type' => $request->device_type,
            ],
            [
                'token' => $request->token,
            ]
        );

        return response()->json(['message' => 'Token registrado exitosamente']);
    }
}
