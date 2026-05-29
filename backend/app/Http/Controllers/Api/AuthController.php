<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * AuthController — Gestión de autenticación via Laravel Sanctum.
 *
 * Endpoints:
 *   POST /api/auth/login   → Obtener token
 *   POST /api/auth/logout  → Revocar token actual
 *   GET  /api/auth/me      → Perfil del usuario autenticado
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión y obtener un token de acceso API.
     *
     * @param  LoginRequest  $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Verificar credenciales
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
                'error'   => 'INVALID_CREDENTIALS',
            ], 401);
        }

        // Verificar que el usuario esté activo
        if (! $user->is_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
                'error'   => 'ACCOUNT_DISABLED',
            ], 403);
        }

        // Revocar tokens anteriores del mismo dispositivo (opcional)
        $user->tokens()->where('name', 'api_token')->delete();

        // Crear nuevo token Sanctum
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'token'   => $token,
            'user'    => $this->formatUserProfile($user),
        ], 200);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        $message = 'Si el correo existe y la cuenta esta activa, enviaremos instrucciones para recuperar la contrasena.';

        if (! $user || ! $user->is_active) {
            return response()->json(['message' => $message], 200);
        }

        $token = Str::random(64);
        $user->forceFill([
            'password_reset_token' => Hash::make($token),
            'password_reset_sent_at' => now(),
        ])->save();

        $resetUrl = rtrim((string) config('app.frontend_url'), '/') . '/reset-password?email=' . urlencode($user->email) . '&token=' . urlencode($token);

        Mail::raw(
            "Hola {$user->full_name},\n\nRecibimos una solicitud para restablecer tu contrasena en SISGENOT.\n\nToken de recuperacion: {$token}\n\nTambien puedes usar este enlace:\n{$resetUrl}\n\nEste codigo vence en 60 minutos. Si no solicitaste este cambio, ignora este mensaje.",
            function ($mail) use ($user) {
                $mail->to($user->email)
                    ->subject('Recuperacion de contrasena - SISGENOT');
            }
        );

        $response = ['message' => $message];

        if (app()->environment('local')) {
            $response['reset_token'] = $token;
        }

        return response()->json($response, 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'password.regex' => 'La contrasena debe incluir mayusculas, minusculas y numeros.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->password_reset_token || ! $user->password_reset_sent_at) {
            return response()->json(['message' => 'El token de recuperacion no es valido.'], 422);
        }

        if ($user->password_reset_sent_at->lt(now()->subMinutes(60))) {
            return response()->json(['message' => 'El token de recuperacion expiro. Solicita uno nuevo.'], 422);
        }

        if (! Hash::check($validated['token'], $user->password_reset_token)) {
            return response()->json(['message' => 'El token de recuperacion no es valido.'], 422);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'password_reset_token' => null,
            'password_reset_sent_at' => null,
            'must_change_password' => false,
        ])->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Contrasena actualizada correctamente. Ya puedes iniciar sesion.',
        ], 200);
    }

    /**
     * Cerrar sesión revocando el token actual.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar solo el token utilizado en esta solicitud
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    /**
     * Obtener el perfil completo del usuario autenticado.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUserProfile($user),
        ], 200);
    }

    /**
     * Formatea el perfil del usuario incluyendo datos según su rol.
     *
     * @param  User  $user
     * @return array
     */
    private function formatUserProfile(User $user): array
    {
        $profile = [
            'id'        => $user->id,
            'email'     => $user->email,
            'full_name' => $user->full_name,
            'role'      => $user->role,
            'is_active' => $user->is_active,
            'created_at'=> $user->created_at,
        ];

        // Agregar datos del perfil según el rol
        if ($user->isTeacher() && $user->teacher) {
            $profile['teacher_profile'] = [
                'id'          => $user->teacher->id,
                'document_id' => $user->teacher->document_id,
            ];
        }

        if ($user->isStudent() && $user->student) {
            $student = $user->student->load('course');
            $profile['student_profile'] = [
                'id'              => $student->id,
                'document_id'     => $student->document_id,
                'enrollment_code' => $student->enrollment_code,
                'course'          => $student->course ? [
                    'id'    => $student->course->id,
                    'name'  => $student->course->name,
                    'grade' => $student->course->grade,
                    'year'  => $student->course->year,
                ] : null,
            ];
        }

        return $profile;
    }
}
