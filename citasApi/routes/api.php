<?php

use App\Http\Controllers\CitasController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\DoctoresController;
use App\Http\Controllers\EspecialidadesController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\HorariosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('listarPacientes', [PacientesController::class, 'index']);
Route::get('paciente/{id}', [PacientesController::class, 'show']);
Route::post('crearPaciente', [PacientesController::class, 'store']);
Route::put('actualizarPaciente/{id}', [PacientesController::class, 'update']);
Route::delete('eliminarPaciente/{id}', [PacientesController::class, 'destroy']);

Route::get('listarDoctores', [DoctoresController::class, 'index']);
Route::get('doctor/{id}', [DoctoresController::class, 'show']);
Route::post('crearDoctor', [DoctoresController::class, 'store']);
Route::put('actualizarDoctor/{id}', [DoctoresController::class, 'update']);
Route::delete('eliminarDoctor/{id}', [DoctoresController::class, 'destroy']);

Route::get('listarCitas', [CitasController::class, 'index']);
Route::get('cita/{id}', [CitasController::class, 'show']);
Route::post('crearCita', [CitasController::class, 'store']);
Route::put('actualizarCita/{id}', [CitasController::class, 'update']);
Route::delete('eliminarCita/{id}', [CitasController::class, 'destroy']);

Route::get('listarEspecialidades', [EspecialidadesController::class, 'index']);
Route::get('especialidad/{id}', [EspecialidadesController::class, 'show']);
Route::post('crearEspecialidad', [EspecialidadesController::class, 'store']);
Route::put('actualizarEspecialidad/{id}', [EspecialidadesController::class, 'update']);
Route::delete('eliminarEspecialidad/{id}', [EspecialidadesController::class, 'destroy']);

Route::get('listarHorarios', [HorariosController::class, 'index']);
Route::get('horario/{id}', [HorariosController::class, 'show']);
Route::post('crearHorario', [HorariosController::class, 'store']);
Route::put('actualizarHorario/{id}', [HorariosController::class, 'update']);
Route::delete('eliminarHorario/{id}', [HorariosController::class, 'destroy']);



Route::get('doctoresEspecialidad', [ConsultaController::class, 'doctoresConEspecialidad']);
Route::get('pacientesCitas', [ConsultaController::class, 'pacientesConCitas']);
Route::get('proximaCita', [ConsultaController::class, 'proximaCitaPorPaciente']);
Route::get('cantidadDoctoresEspecialidad', [ConsultaController::class, 'cantidadDoctoresPorEspecialidad']);
Route::get('horariosDoctor/{id}', [ConsultaController::class, 'horariosPorDoctor']);
Route::get('doctoresMasCitas', [ConsultaController::class, 'doctoresConMasDeCincoCitas']);
Route::get('pacientesGenero', [ConsultaController::class, 'pacientesPorGenero']);
Route::get('ultimaCita', [ConsultaController::class, 'ultimaCitaPorPaciente']);
Route::get('especialidadMasSolicitada', [ConsultaController::class, 'especialidadMasSolicitada']);
Route::get('citasPorDiaDoctor/{id}', [ConsultaController::class, 'citasPorDiaDoctor']);
