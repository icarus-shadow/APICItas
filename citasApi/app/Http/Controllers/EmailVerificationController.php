<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    /**
     * Enviar código de verificación por email
     */
    public function sendVerificationCode(Request $request)
    {
        // Validar el email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email inválido',
                'errors' => $validator->errors()
            ], 400);
        }

        $email = $request->input('email');

        // Generar código de 6 dígitos
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Almacenar en cache con expiración de 10 minutos
        Cache::put('verification_code_' . $email, $code, now()->addMinutes(10));

        try {
            // Enviar email con la vista Blade
            Mail::send('emails.verification', ['code' => $code], function ($message) use ($email) {
                $message->to($email)
                        ->subject('Código de Verificación - Citas App');
            });

            return response()->json([
                'success' => true,
                'message' => 'Código de verificación enviado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar el código de verificación
     */
    public function verifyCode(Request $request)
    {
        // Validar los datos
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $email = $request->input('email');
        $code = $request->input('code');

        // Obtener el código almacenado en cache
        $storedCode = Cache::get('verification_code_' . $email);

        if (!$storedCode || $storedCode !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Código de verificación inválido o expirado'
            ], 400);
        }

        // Generar token temporal de verificación
        $token = Str::random(64);

        // Almacenar token temporal (puedes ajustar la expiración según necesites)
        Cache::put('verification_token_' . $email, $token, now()->addMinutes(30));

        // Limpiar el código usado
        Cache::forget('verification_code_' . $email);

        return response()->json([
            'success' => true,
            'message' => 'Código verificado exitosamente',
            'token' => $token
        ]);
    }
}
