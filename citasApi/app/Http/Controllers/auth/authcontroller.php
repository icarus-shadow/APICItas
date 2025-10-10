<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\Administradores;
use App\Models\Doctores;
use App\Models\Pacientes;
use App\Models\User;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Throwable;


class authcontroller extends Controller
{
    /**
     * @group Autenticación
     * @subgroup Registro de Pacientes
     *
     * Registrar un nuevo paciente
     *
     * Este endpoint permite crear un paciente y su usuario asociado en el sistema. Se ejecuta dentro de
     * una transacción para garantizar la integridad de los datos. El usuario se crea con el rol de paciente (id_rol = 1).
     *
     * @bodyParam email string required Correo electrónico único del usuario. Example: paciente@example.com
     * @bodyParam password string required Contraseña del usuario, mínimo 6 caracteres. Example: secret123
     * @bodyParam nombres string required Nombres completos del paciente. Example: Juan
     * @bodyParam apellidos string required Apellidos completos del paciente. Example: Pérez Gómez
     * @bodyParam documento string required Número de documento único. Example: 123456789
     * @bodyParam rh string required Tipo de sangre del paciente. Example: O+
     * @bodyParam fecha_nacimiento date required Fecha de nacimiento en formato Y-m-d. Example: 1990-05-15
     * @bodyParam genero string required Género del paciente (M o F). Example: M
     * @bodyParam edad string required Edad del paciente. Example: 33
     * @bodyParam telefono string Teléfono de contacto del paciente. Example: 3201234567
     * @bodyParam alergias string Alergias conocidas del paciente. Example: Penicilina
     * @bodyParam comentarios string Comentarios adicionales. Example: Ninguno
     *
     * @response 201 {
     *   "message": "Paciente registrado correctamente",
     *   "user": {
     *       "id": 10,
     *       "email": "paciente@example.com",
     *       "id_rol": 1,
     *       "created_at": "2025-09-05T10:00:00.000000Z"
     *   },
     *   "paciente": {
     *       "id": 5,
     *       "user_id": 10,
     *       "nombres": "Juan",
     *       "apellidos": "Pérez Gómez",
     *       "documento": "123456789",
     *       "rh": "O+",
     *       "fecha_nacimiento": "1990-05-15",
     *       "genero": "M",
     *       "edad": "33",
     *       "telefono": "3201234567",
     *       "alergias": "Penicilina",
     *       "comentarios": "Ninguno"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *       "email": ["El campo email es obligatorio."]
     *   }
     * }
     *
     * @response 500 {
     *   "message": "Error al registrar paciente",
     *   "error": "Detalle del error interno"
     * }
     * @throws Throwable
     */

    public function registerPaciente(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'documento' => 'required|unique:pacientes,documento',
            'rh' => 'required|string',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:M,F',
            'edad' => 'required|string',
            'telefono' => 'nullable|string',
            'alergias' => 'nullable|string',
            'comentarios' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'id_rol' => 1
            ]);

            $paciente = Pacientes::create([
                'user_id' => $user->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'documento' => $request->documento,
                'rh' => $request->rh,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'genero' => $request->genero,
                'edad' => $request->edad,
                'telefono' => $request->telefono,
                'alergias' => $request->alergias,
                'comentarios' => $request->comentarios
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paciente registrado correctamente',
                'user' => $user,
                'paciente' => $paciente
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar paciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Autenticación
     * @subgroup Registro de Administradores
     *
     * Registrar un nuevo administrador
     *
     * Este endpoint permite crear un administrador y su usuario asociado en el sistema. Se ejecuta dentro de
     * una transacción para garantizar la integridad de los datos. El usuario se crea con el rol de administrador (id_rol = 3).
     *
     * @bodyParam email string required Correo electrónico único del usuario. Example: admin@example.com
     * @bodyParam password string required Contraseña del usuario, mínimo 6 caracteres. Example: secret123
     * @bodyParam nombres string required Nombres completos del administrador. Example: María
     * @bodyParam apellidos string required Apellidos completos del administrador. Example: Rodríguez López
     * @bodyParam cedula string required Cédula única del administrador. Example: 987654321
     * @bodyParam telefono string required Teléfono de contacto del administrador. Example: 3109876543
     *
     * @response 201 {
     *   "message": "Administrador registrado correctamente",
     *   "user": {
     *       "id": 12,
     *       "email": "admin@example.com",
     *       "id_rol": 3,
     *       "created_at": "2025-09-05T10:00:00.000000Z"
     *   },
     *   "administrador": {
     *       "id": 3,
     *       "user_id": 12,
     *       "nombres": "María",
     *       "apellidos": "Rodríguez López",
     *       "cedula": "987654321",
     *       "telefono": "3109876543"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *       "email": ["El campo email es obligatorio."]
     *   }
     * }
     *
     * @response 500 {
     *   "message": "Error al registrar administrador",
     *   "error": "Detalle del error interno"
     * }
     * @throws Throwable
     */

    public function registerAdministrador(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'cedula' => 'required|string|unique:administradores,cedula',
            'telefono' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'id_rol' => 3
            ]);

            $administrador = Administradores::create([
                'user_id' => $user->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'cedula' => $request->cedula,
                'telefono' => $request->telefono,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Administrador registrado correctamente',
                'user' => $user,
                'administrador' => $administrador
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @group Autenticación
     * @subgroup Registro de Doctores
     *
     * Registrar un nuevo doctor
     *
     * Este endpoint permite crear un doctor y su usuario asociado en el sistema. Se ejecuta dentro de
     * una transacción para garantizar la integridad de los datos. El usuario se crea con el rol de doctor (id_rol = 2).
     *
     * @bodyParam email string required Correo electrónico único del usuario. Example: doctor@example.com
     * @bodyParam password string required Contraseña del usuario, mínimo 6 caracteres. Example: secret123
     * @bodyParam nombres string required Nombres completos del doctor. Example: Andrés
     * @bodyParam apellidos string required Apellidos completos del doctor. Example: García López
     * @bodyParam cedula string required Cédula única del doctor. Example: 1122334455
     * @bodyParam id_especialidades integer required ID de la especialidad a la que pertenece el doctor. Example: 1
     * @bodyParam id_horario integer ID de la plantilla de horario a asignar (opcional). Example: 1
     * @bodyParam lugar_trabajo string Lugar donde atiende el doctor. Example: Hospital Central
     *
     * @response 201 {
     *   "message": "Doctor registrado correctamente",
     *   "user": {
     *       "id": 15,
     *       "email": "doctor@example.com",
     *       "id_rol": 2,
     *       "created_at": "2025-09-05T10:00:00.000000Z"
     *   },
     *   "doctor": {
     *       "id": 7,
     *       "user_id": 15,
     *       "nombres": "Andrés",
     *       "apellidos": "García López",
     *       "cedula": "1122334455",
     *       "id_especialidades": 1,
     *       "horario": "Lunes a Viernes 8:00 AM - 4:00 PM",
     *       "lugar_trabajo": "Hospital Central"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *       "email": ["El campo email es obligatorio."]
     *   }
     * }
     *
     * @response 500 {
     *   "message": "Error al registrar doctor",
     *   "error": "Detalle del error interno"
     * }
     * @throws Throwable
     */



    public function registerDoctor(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'cedula' => 'required|string|unique:doctores,cedula',
            'id_especialidades' => 'required|exists:especialidades,id',
            'id_horario' => 'nullable|exists:horarios,id',
            'lugar_trabajo' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'id_rol' => 2
            ]);

            $doctor = Doctores::create([
                'user_id' => $user->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'cedula' => $request->cedula,
                'id_especialidades' => $request->id_especialidades,
                'horario' => $request->horario ?? null,
                'lugar_trabajo' => $request->lugar_trabajo
            ]);

            // Si se especificó un horario, asignarlo al doctor
            if ($request->id_horario) {
                $horarioTemplate = \App\Models\Horarios::find($request->id_horario);

                // Verificar conflictos antes de asignar
                $horariosController = new \App\Http\Controllers\HorariosController();
                $conflictos = $horariosController->checkHorarioConflicts($doctor->id, $horarioTemplate);
                if (!empty($conflictos)) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Conflicto de horarios detectado',
                        'conflictos' => $conflictos,
                        'message' => 'No se puede asignar este horario porque hay conflictos.'
                    ], 422);
                }

                // Asignar el horario
                $hora_inicio = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_inicio);
                $hora_fin = \DateTime::createFromFormat('H:i', $horarioTemplate->hora_fin);
                $interval = new \DateInterval('PT30M');

                foreach ($horarioTemplate->dias as $dia) {
                    $period = new \DatePeriod($hora_inicio, $interval, $hora_fin);

                    foreach ($period as $start) {
                        $end = clone $start;
                        $end->add($interval);

                        \App\Models\DoctorHorario::create([
                            'id_horario' => $horarioTemplate->id,
                            'id_doctor' => $doctor->id,
                            'dia' => $dia,
                            'hora_inicio' => $start->format('H:i'),
                            'hora_fin' => $end->format('H:i'),
                            'disponible' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Doctor registrado correctamente',
                'user' => $user,
                'doctor' => $doctor
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @group Autenticación
     * @subgroup Inicio de Sesión
     *
     * Iniciar sesión
     *
     * Este endpoint permite a un usuario autenticarse en el sistema y obtener un token de acceso.
     *
     * @bodyParam email string required Correo electrónico del usuario. Example: user@example.com
     * @bodyParam password string required Contraseña del usuario. Example: secret123
     *
     * @response 200 {
     *   "access_token": "1|abc123...",
     *   "token_type": "Bearer",
     *   "user": {
     *       "id": 1,
     *       "email": "user@example.com",
     *       "id_rol": 1
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *       "email": ["Credenciales incorrectas"]
     *   }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * @group Autenticación
     * @subgroup Cierre de Sesión
     *
     * Cerrar sesión
     *
     * Este endpoint permite a un usuario autenticado cerrar su sesión actual, revocando todos sus tokens.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Sesión cerrada correctamente"
     * }
     *
     * @response 401 {
     *   "message": "Usuario no autenticado"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    /**
     * @group Autenticación
     * @subgroup Gestión de Contraseña
     *
     * Cambiar contraseña del usuario
     *
     * Este endpoint permite que un usuario autenticado cambie su propia contraseña.
     *
     * @bodyParam current_password string required Contraseña actual del usuario. Example: oldpassword123
     * @bodyParam new_password string required Nueva contraseña del usuario, mínimo 6 caracteres. Example: newpassword123
     *
     * @response 200 {
     *   "message": "Contraseña actualizada correctamente"
     * }
     *
     * @response 401 {
     *   "message": "Usuario no autenticado"
     * }
     *
     * @response 409 {
     *   "message": "La contraseña actual es incorrecta"
     * }
     *
     * @response 422 {
     *   "errors": {
     *       "new_password": ["La nueva contraseña debe tener al menos 6 caracteres"]
     *   }
     * }
     *
     * @authenticated
     */


    public function changePassword(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6'
        ]);

        $user = $request->user();


        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta'
            ], 409);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();


        return response()->json([
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    /**
     * @group Autenticación
     * @subgroup Eliminación de Cuenta
     *
     * Eliminar cuenta de usuario
     *
     * Este endpoint permite que un usuario autenticado elimine su propia cuenta y todos los datos asociados.
     * Se ejecuta dentro de una transacción para garantizar la integridad de los datos.
     *
     * @bodyParam password string required Contraseña actual del usuario para confirmar la eliminación. Example: mypassword123
     *
     * @response 200 {
     *   "message": "Cuenta eliminada correctamente"
     * }
     *
     * @response 401 {
     *   "message": "Usuario no autenticado"
     * }
     *
     * @response 422 {
     *   "message": "La contraseña es incorrecta"
     * }
     *
     * @response 500 {
     *   "message": "Error al eliminar la cuenta",
     *   "error": "Detalle del error interno"
     * }
     *
     * @authenticated
     * @throws Throwable
     */
    public function deleteAccount(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $request->validate([
            'password' => 'required'
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña es incorrecta'
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($user->id_rol === 1) {
                Pacientes::where('user_id', $user->id)->delete();
            } else if ($user->id_rol === 2) {
                Doctores::where('user_id', $user->id)->delete();
            } else if ($user->id_rol === 3) {
                Administradores::where('user_id', $user->id)->delete();
            }

            $user->delete();
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'message' => 'Cuenta eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
