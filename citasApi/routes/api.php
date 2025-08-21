<?php

use App\Http\Controllers\PacientesController;
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
