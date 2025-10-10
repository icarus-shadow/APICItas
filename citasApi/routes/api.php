<?php

use App\Http\Controllers\AdministradoresController;
use App\Http\Controllers\CitasController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\DoctoresController;
use App\Http\Controllers\EspecialidadesController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\HorariosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\auth\authcontroller;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [authController::class, 'logout']);
    Route::post('/change-password', [authController::class, 'changePassword']);
    Route::delete('/delete-account', [authController::class, 'deleteAccount']);


    // CRUD para pacientes (solo puede acceder a su propia información)
    Route::get('/mi-perfil', [PacientesController::class, 'showOwn']);        // Ver su información
    Route::put('/mi-perfil', [PacientesController::class, 'updateOwn']);     // Actualizar su información

    // Pacientes y Doctores
    Route::get('/doctores/especialidad/{id}', [ConsultaController::class, 'doctoresPorEspecialidad']);
    Route::get('/doctor/{id}/disponibilidad', [ConsultaController::class, 'disponibilidadDoctor']);
    Route::get('/doctores/{doctorId}/slots', [CitasController::class, 'getAvailableSlots']);
    Route::post('/doctores/{doctorId}/validate-slot', [CitasController::class, 'validateSlot']);

    // Paciente autenticado
    Route::get('/citas/mis-citas', [ConsultaController::class, 'misCitas']);
    Route::post('/citas', [CitasController::class, 'store']);
    Route::put('/citas/paciente/{id}', [CitasController::class, 'updateOwn']);
    Route::delete('/citas/{id}', [CitasController::class, 'destroyOwn']);


});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/mi-perfil-admin', [AdministradoresController::class, 'showOwn']);
    Route::put('/mi-perfil-admin', [AdministradoresController::class, 'updateOwn']);

    Route::post('/registrar-doctor', [authController::class, 'registerDoctor']);
    Route::post('/registrar-administrador', [authController::class, 'registerAdministrador']);

    // CRUD de pacientes
    Route::get('/pacientes', [PacientesController::class, 'index']);           // Listar todos los pacientes
    Route::get('/pacientes/{id}', [PacientesController::class, 'show']);      // Ver un paciente específico
    Route::put('/pacientes/{id}', [PacientesController::class, 'update']);    // Actualizar un paciente
    Route::delete('/pacientes/{id}', [PacientesController::class, 'destroy']); // Eliminar un paciente
    Route::get('/countPacientes', [PacientesController::class, 'count']);

    // crud de doctores
    Route::get('/doctor/{id}', [DoctoresController::class, 'show']);     // Ver un doctor específico
    Route::put('/doctor/{id}', [DoctoresController::class, 'update']);   // Actualizar doctor
    Route::delete('/doctor/{id}', [DoctoresController::class, 'destroy']);
    Route::get('/countDoctores', [DoctoresController::class, 'count']);

    // crud de especialidades para admin
    Route::post('/especialidades', [EspecialidadesController::class, 'store']);    // Crear
    Route::put('/especialidades/{id}', [EspecialidadesController::class, 'update']); // Actualizar
    Route::delete('/especialidades/{id}', [EspecialidadesController::class, 'destroy']); // Eliminar
    Route::get('/countEspecialidades', [EspecialidadesController::class, 'count']);

    // endpoints para la administracion de horarios
    Route::get('/horarios', [HorariosController::class, 'index']); // Listar plantillas de horario
    Route::get('/horarios/{id}', [HorariosController::class, 'show']);    // Ver un horario
    Route::post('/horarios', [HorariosController::class, 'store']);       // Crear plantilla de horario
    Route::post('/asignar-horario', [HorariosController::class, 'assignToDoctor']); // Asignar horario a doctor
    Route::post('/desasignar-horario', [HorariosController::class, 'unassignFromDoctor']); // Desasignar horario de doctor
    Route::post('/verificar-conflicto-horario', [HorariosController::class, 'checkConflict']); // Verificar conflictos antes de asignar
    Route::put('/horarios/{id}', [HorariosController::class, 'update']);  // Editar un bloque específico
    Route::delete('/horarios/{id}', [HorariosController::class, 'destroy']); // Eliminar un bloque
    Route::get('/horarios-doctor/{id_doctor}', [HorariosController::class, 'listByDoctor']); // Listar por doctor
    Route::get('/countHorarios', [HorariosController::class, 'count']);

    // Notificaciones
    Route::get('/notificaciones', [\App\Http\Controllers\NotificacionesController::class, 'index']); // Listar pendientes
    Route::put('/notificaciones/{id}', [\App\Http\Controllers\NotificacionesController::class, 'update']); // Aprobar/rechazar

    // administacion de citas
    Route::get('/citas', [CitasController::class, 'index']); // Ver todas
    Route::get('/citas/{id}', [CitasController::class, 'show']); // Ver una cita
    Route::delete('/citas/{id}', [CitasController::class, 'destroy']); // Eliminar cualquier cita
    Route::put('/citas/{id}', [CitasController::class, 'update']);// Editar cualquier cita
    Route::get('/countCitas', [CitasController::class, 'countCitas']);

    Route::get('/administradores', [AdministradoresController::class, 'index']);
    Route::get('/administradores/{id}', [AdministradoresController::class, 'show']);
    Route::put('/administradores/{id}', [AdministradoresController::class, 'update']);
    Route::delete('/administradores/{id}', [AdministradoresController::class, 'destroy']);
    Route::get('/countAdministradores', [AdministradoresController::class, 'count']);

    Route::get('/reportes/citas-por-especialidad', [ConsultaController::class, 'reporteCitasPorEspecialidad']);

    // Notificaciones
    Route::get('/notificaciones/activas', [\App\Http\Controllers\NotificacionesController::class, 'index']);
    Route::get('/notificaciones/historial', [\App\Http\Controllers\NotificacionesController::class, 'historial']);
    Route::post('/notificaciones/{id}/aprobar', [\App\Http\Controllers\NotificacionesController::class, 'aprobar']);
    Route::post('/notificaciones/{id}/rechazar', [\App\Http\Controllers\NotificacionesController::class, 'rechazar']);
    Route::delete('/notificaciones/historial', [\App\Http\Controllers\NotificacionesController::class, 'eliminarHistorial']);
    Route::get('/notificaciones/contadores', [\App\Http\Controllers\NotificacionesController::class, 'contadores']);

    // Horarios
    Route::get('/horarios/compactados/{id_doctor}', [ConsultaController::class, 'horariosCompactados']);
});

Route::middleware(['auth:sanctum', 'doctor'])->group(function () {
    Route::get('/mi-perfil-doctor', [DoctoresController::class, 'showOwn']);       // Ver su perfil
    Route::put('/mi-perfil-doctor', [DoctoresController::class, 'updateOwn']);     // Actualizar su perfil
    Route::delete('/mi-perfil-doctor', [DoctoresController::class, 'destroyOwn']); // Eliminar su cuenta

    // manejo de horarios disponibles por el doctor
    Route::get('/mis-horarios', [HorariosController::class, 'listOwn']);   // Listar mis horarios completos
    Route::post('/notificaciones', [\App\Http\Controllers\NotificacionesController::class, 'store']); // Crear solicitud
    Route::get('/mis-notificaciones', [\App\Http\Controllers\NotificacionesController::class, 'myNotifications']); // Ver mis notificaciones

    // Doctor autenticado
    Route::get('/doctor/mis-pacientes', [ConsultaController::class, 'pacientesPorDoctor']);// Eliminar un bloque mío
});

Route::post('/login', [authController::class, 'login']);
Route::get('/doctores', [DoctoresController::class, 'index']);       // Listar doctores
Route::get('/especialidades', [EspecialidadesController::class, 'index']); // Listar
Route::get('/especialidades/{id}', [EspecialidadesController::class, 'show']); // Ver detalle
Route::post('/registrar-paciente', [authController::class, 'registerPaciente']);



