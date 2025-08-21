<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CitasController extends Controller
{
    public function index()
    {
        $citas = Citas::all();
        return response()->json($citas);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_cita' => 'date',
            'hora_cita' => 'string|max:255',
            'lugar' => 'string|max:255',
            'id_doctor' => 'exists:doctores,id',
            'id_paciente' => 'exists:pacientes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita = Citas::create($validator->validated());
        return response()->json($cita, 201);
    }

    public function show($id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrado'], 404);
        }
        return response()->json($cita);
    }

    public function  update(Request $request, string $id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'fecha_cita' => 'date',
            'hora_cita' => 'string|max:255',
            'lugar' => 'string|max:255',
            'id_doctor' => 'exists:doctores,id',
            'id_paciente' => 'exists:pacientes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update($validator->validated());

        return  response()->json($cita);
    }

    public function destroy(string $id)
    {
        $cita = Citas::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrado'], 404);
        }
        $cita->delete();
        return response()->json(['message' => 'Cita eliminado con exito']);
    }

}
