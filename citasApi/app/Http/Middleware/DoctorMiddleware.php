<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DoctorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->id_rol === 2) {
            return $next($request);
        }

        return response()->json(['message' => 'Acceso denegado'], 403);
    }
}

