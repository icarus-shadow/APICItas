<?php

namespace App\Http\Controllers;

use App\Models\Horarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HorariosController extends Controller
{
    /**
     * @group Horarios [ADMIN]
     *
     * Listar todos los horarios (compacto)
     *
     * Devuelve todos los horarios del sistema agrupados por rango grande (no por intervalos de 30 minutos).
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "id_doctor": 1,
     *      "nombre_doctor": "Juan Pérez",
     *      "dia": 1,
     *      "rango": "08:00 - 12:00"
     *   }
     * ]
     */
    public function indexCompact()
    {
        $horarios = Horarios::select('id_doctor', 'dia')
            ->selectRaw('MIN(hora_inicio) as hora_inicio, MAX(hora_fin) as hora_fin')
            ->groupBy('id_doctor', 'dia')
            ->get();

        return response()->json($horarios);
    }

    /**
     * @group Horarios [ADMIN]
     *
     * Listar horarios por doctor
     *
     * Devuelve todos los intervalos de 30 minutos de un doctor específico.
     *
     * @authenticated
     *
     * @urlParam id_doctor integer ID del doctor. Example: 1
     *
     * @response 200 [
     *   {
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30"
     *   }
     * ]
     */
    public function listByDoctor($id_doctor)
    {
        $horarios = Horarios::where('id_doctor', $id_doctor)->get();
        return response()->json($horarios);
    }

    /**
     * @group Horarios [DOCTOR]
     *
     * Listar mis horarios
     *
     * Devuelve todos los intervalos de 30 minutos del doctor autenticado.
     *
     * @authenticated
     *
     * @response 200 [
     *   {
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30"
     *   }
     * ]
     */
    public function listOwn()
    {
        $horarios = Horarios::where('id_doctor', auth()->user()->doctor->id)->get();
        return response()->json($horarios);
    }

    /**
     * @group Horarios [ADMIN/DOCTOR]
     *
     * Crear horarios
     *
     * Crea horarios para un doctor a partir de un rango amplio, dividiéndolos en intervalos de 30 minutos.
     *
     * @authenticated
     *
     * @bodyParam id_doctor integer ID del doctor (requerido para admin). Example: 1
     * @bodyParam dias array Lista de días (1=Lunes, 7=Domingo). Example: [1,3,5]
     * @bodyParam hora_inicio string Hora de inicio (HH:mm). Example: 08:00
     * @bodyParam hora_fin string Hora de fin (HH:mm). Example: 12:00
     *
     * @response 201 [
     *   {
     *      "id_doctor": 1,
     *      "dia": 1,
     *      "hora_inicio": "08:00",
     *      "hora_fin": "08:30"
     *   }
     * ]
     */
    public function store(Request $request)
    {
        $rules = [
            'dias' => 'required|array|min:1',
            'dias.*' => 'integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ];

        if (auth()->user()->id_rol == 1) {
            $rules['id_doctor'] = 'required|exists:doctores,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $id_doctor = $data['id_doctor'] ?? auth()->user()->doctor->id;
        $dias = $data['dias'];
        $hora_inicio = \DateTime::createFromFormat('H:i', $data['hora_inicio']);
        $hora_fin = \DateTime::createFromFormat('H:i', $data['hora_fin']);
        $interval = new \DateInterval('PT30M');

        $createdHorarios = [];

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

    /**
     * @group Horarios [ADMIN/DOCTOR]
     *
     * Ver un horario
     *
     * Devuelve la información de un intervalo de 30 minutos específico.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     *
     * @response 200 {
     *    "id": 1,
     *    "id_doctor": 1,
     *    "dia": 1,
     *    "hora_inicio": "08:00",
     *    "hora_fin": "08:30"
     * }
     */
    public function show($id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        return response()->json($horario);
    }

    /**
     * @group Horarios [ADMIN/DOCTOR]
     *
     * Editar un intervalo
     *
     * Permite editar un intervalo de 30 minutos específico.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     * @bodyParam hora_inicio string Hora de inicio. Example: 09:00
     * @bodyParam hora_fin string Hora de fin. Example: 09:30
     *
     * @response 200 {
     *    "id": 1,
     *    "hora_inicio": "09:00",
     *    "hora_fin": "09:30"
     * }
     */
    public function update(Request $request, $id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $horario->update($validator->validated());

        return response()->json($horario);
    }

    /**
     * @group Horarios [ADMIN/DOCTOR]
     *
     * Eliminar un intervalo
     *
     * Permite eliminar un intervalo de 30 minutos.
     *
     * @authenticated
     *
     * @urlParam id integer ID del horario. Example: 1
     *
     * @response 200 {
     *    "message": "Horario eliminado con éxito"
     * }
     */
    public function destroy($id)
    {
        $horario = Horarios::find($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
        $horario->delete();
        return response()->json(['message' => 'Horario eliminado con éxito']);
    }
}
