<?php

use App\Http\Controllers\CitasController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\DoctoresController;
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


