<?php

namespace App\Http\Controllers;

use App\Models\Doctores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctoresController extends Controller
{
    public function index()
    {
        $doctores = Doctores::all();
        return response()->json($doctores);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'horario' => 'required|string|max:255',
            'lugar_trabajo' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor = Doctores::create($validator->validated());
        return response()->json($doctor, 201);
    }

    public function show($id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        return response()->json($doctor);
    }

    public function  update(Request $request, string $id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'apellido' => 'string|max:255',
            'especialidad' => 'string|max:255',
            'horario' => 'string|max:255',
            'lugar_trabajo' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor->update($validator->validated());

        return  response()->json($doctor);
    }

    public function destroy(string $id)
    {
        $doctor = Doctores::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor no encontrado'], 404);
        }
        $doctor->delete();
        return response()->json(['message' => 'Doctor eliminado con exito']);
    }
}
