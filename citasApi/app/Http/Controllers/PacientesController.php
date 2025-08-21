<?php

namespace App\Http\Controllers;

use App\Models\Pacientes;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PacientesController extends Controller
{
    public function index()
    {
        $pacientes = Pacientes::all();
        return response()->json($pacientes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'documento' => 'required|string|max:255',
            'rh' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required',
            'edad' => 'required|integer',
            'email' => 'required|string|email|max:255',
            'telefono' => 'required|string|max:255',
            'alergias' => 'nullable|string|max:255',
            'comentarios' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente = Pacientes::create($validator->validated());
        return response()->json($paciente, 201);
    }

    public function show($id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        return response()->json($paciente);
    }

    public function  update(Request $request, string $id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'string|max:255',
            'apellidos' => 'string|max:255',
            'documento' => 'string|max:255',
            'rh' => 'string|max:255',
            'fecha_nacimiento' => '|date',
            'genero' => '',
            'edad' => 'integer',
            'email' => 'string|email|max:255',
            'telefono' => 'string|max:255',
            'alergias' => 'nullable|string|max:255',
            'comentarios' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente->update($validator->validated());

        return  response()->json($paciente);
    }

    public function destroy(string $id)
    {
        $paciente = Pacientes::find($id);
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        $paciente->delete();
        return response()->json(['message' => 'Paciente eliminado con exito']);
    }

}
