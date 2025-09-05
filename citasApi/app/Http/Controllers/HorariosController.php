<?php

namespace App\Http\Controllers;

use App\Models\Horarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HorariosController extends Controller
{
    public function index()
    {
        $horarios = Horarios::all();
        return response()->json($horarios);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_doctor' => 'required|exists:doctores,id',
            'dias' => 'required|array|min:1',
            'dias.*' => 'integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $id_doctor = $data['id_doctor'];
        $dias = $data['dias'];
        $hora_inicio_str = $data['hora_inicio'];
        $hora_fin_str = $data['hora_fin'];

        $hora_inicio = \DateTime::createFromFormat('H:i', $hora_inicio_str);
        $hora_fin = \DateTime::createFromFormat('H:i', $hora_fin_str);

        $createdHorarios = [];

        $interval = new \DateInterval('PT30M');

        foreach ($dias as $dia) {
            $period = new \DatePeriod($hora_inicio, $interval, $hora_fin);

            foreach ($period as $start) {
                $end = clone $start;
                $end->add($interval);

                $horario = Horarios::create([
                    'id_doctor' => $id_doctor,
                    'dia' => $dia,
                    'hora_inicio' => $start->format('H:i'),
                    'hora_fin' => $end->format('H:i'),
                    'disponible' => true,
                ]);

                $createdHorarios[] = $horario;
            }
        }

        return response()->json($createdHorarios, 201);
    }

    public function show($id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        return response()->json($horario);
    }

    public function update(Request $request, $id_doctor)
    {
        $validator = Validator::make($request->all(), [
            'dias' => 'required|array|min:1',
            'dias.*' => 'integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        Horarios::where('id_doctor', $id_doctor)->delete();

        $dias = $data['dias'];
        $hora_inicio_str = $data['hora_inicio'];
        $hora_fin_str = $data['hora_fin'];

        $hora_inicio = \DateTime::createFromFormat('H:i', $hora_inicio_str);
        $hora_fin = \DateTime::createFromFormat('H:i', $hora_fin_str);

        $createdHorarios = [];
        $interval = new \DateInterval('PT30M');

        foreach ($dias as $dia) {
            $period = new \DatePeriod($hora_inicio, $interval, $hora_fin);

            foreach ($period as $start) {
                $end = clone $start;
                $end->add($interval);

                $horario = Horarios::create([
                    'id_doctor' => $id_doctor,
                    'dia' => $dia,
                    'hora_inicio' => $start->format('H:i'),
                    'hora_fin' => $end->format('H:i'),
                    'disponible' => true,
                ]);

                $createdHorarios[] = $horario;
            }
        }

        return response()->json([
            'message' => 'Horarios actualizados correctamente',
            'horarios' => $createdHorarios
        ], 200);
    }

    public function destroy(string $id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        $horario->delete();
        return response()->json(['message' => 'Horario eliminado con éxito']);
    }
}
