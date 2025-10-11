<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrDoctorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && in_array($request->user()->id_rol, [2, 3])) {
            return $next($request);
        }

        return response()->json(['message' => 'Acceso denegado'], 403);
    }
}
