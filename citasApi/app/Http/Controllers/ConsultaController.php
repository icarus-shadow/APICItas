<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    // 1. Doctores con su especialidad
    public function doctoresConEspecialidad() {
        $data = DB::table('doctores')
            ->join('especialidades', 'doctores.id_especialidades', '=', 'especialidades.id')
            ->select('doctores.id','doctores.nombre','doctores.apellido','especialidades.nombre as especialidad')
            ->get();
        return response()->json($data, 200);
    }

    // 2. Pacientes con sus citas
    public function pacientesConCitas() {
        $data = DB::table('pacientes')
            ->join('citas', 'pacientes.id', '=', 'citas.id_paciente')
            ->select('pacientes.nombres','pacientes.apellidos','citas.fecha_cita','citas.hora_cita','citas.lugar')
            ->get();
        return response()->json($data, 200);
    }

    // 3. Próxima cita por paciente
    public function proximaCitaPorPaciente() {
        $data = DB::table('pacientes')
            ->join('citas', 'pacientes.id', '=', 'citas.id_paciente')
            ->where('citas.fecha_cita', '>=', DB::raw('CURDATE()'))
            ->select('pacientes.nombres','pacientes.apellidos', DB::raw('MIN(citas.fecha_cita) as proxima_cita'))
            ->groupBy('pacientes.id','pacientes.nombres','pacientes.apellidos')
            ->get();
        return response()->json($data, 200);
    }

    // 4. Cantidad de doctores por especialidad
    public function cantidadDoctoresPorEspecialidad() {
        $data = DB::table('especialidades')
            ->join('doctores', 'especialidades.id', '=', 'doctores.id_especialidades')
            ->select('especialidades.nombre', DB::raw('COUNT(doctores.id) as total_doctores'))
            ->groupBy('especialidades.nombre')
            ->get();
        return response()->json($data, 200);
    }

    // 5. Horarios de un doctor específico
    public function horariosPorDoctor($doctorId) {
        $data = DB::table('horarios')
            ->where('horarios.id_doctor', $doctorId)
            ->select('horarios.dia','horarios.hora_inicio','horarios.hora_fin')
            ->get();
        return response()->json($data, 200);
    }

    // 6. Doctores con más de 5 citas
    public function doctoresConMasDeCincoCitas() {
        $data = DB::table('doctores')
            ->join('citas', 'doctores.id', '=', 'citas.id_doctor')
            ->select('doctores.nombre','doctores.apellido', DB::raw('COUNT(citas.id) as total_citas'))
            ->groupBy('doctores.id','doctores.nombre','doctores.apellido')
            ->havingRaw('COUNT(citas.id) > 5')
            ->get();
        return response()->json($data, 200);
    }

    // 7. Pacientes por género
    public function pacientesPorGenero() {
        $data = DB::table('pacientes')
            ->select('genero', DB::raw('COUNT(*) as total'))
            ->groupBy('genero')
            ->get();
        return response()->json($data, 200);
    }

    // 8. Última cita de cada paciente
    public function ultimaCitaPorPaciente() {
        $data = DB::table('pacientes')
            ->join('citas', 'pacientes.id', '=', 'citas.id_paciente')
            ->select('pacientes.nombres','pacientes.apellidos', DB::raw('MAX(citas.fecha_cita) as ultima_cita'))
            ->groupBy('pacientes.id','pacientes.nombres','pacientes.apellidos')
            ->get();
        return response()->json($data, 200);
    }

    // 9. Especialidad más solicitada
    public function especialidadMasSolicitada() {
        $data = DB::table('citas')
            ->join('doctores', 'citas.id_doctor', '=', 'doctores.id')
            ->join('especialidades', 'doctores.id_especialidades', '=', 'especialidades.id')
            ->select('especialidades.nombre', DB::raw('COUNT(citas.id) as total_citas'))
            ->groupBy('especialidades.nombre')
            ->orderByDesc('total_citas')
            ->limit(1)
            ->get();
        return response()->json($data, 200);
    }

    // 10. Citas por día para un doctor
    public function citasPorDiaDoctor($doctorId) {
        $data = DB::table('citas')
            ->where('citas.id_doctor', $doctorId)
            ->select('fecha_cita', DB::raw('COUNT(*) as total_citas'))
            ->groupBy('fecha_cita')
            ->orderBy('fecha_cita', 'asc')
            ->get();
        return response()->json($data, 200);
    }
}
