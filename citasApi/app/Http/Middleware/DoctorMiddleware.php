<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            Log::info("Usuario autenticado: ID {$user->id}, Rol: {$user->id_rol}");
        } else {
            Log::info("No hay usuario autenticado");
        }

        if ($user && $user->id_rol === 2) {
            Log::info("Verificación exitosa: Acceso concedido");
            return $next($request);
        } else {
            Log::info("Verificación fallida: Acceso denegado");
            return response()->json(['message' => 'Acceso denegado: Se requiere rol de Doctor (id_rol=2), tu rol actual es ' . ($user ? $user->id_rol : 'null')], 403);
        }
    }
}

