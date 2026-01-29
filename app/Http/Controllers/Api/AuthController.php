
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            /** @var User $user */
            $user = Auth::user();

            if (! $user->estado) {
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Su cuenta está inactiva. Contacte al administrador.',
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            AuditLogger::login("Usuario {$user->email} inició sesión exitosamente.");

            $roles = $user->roles->pluck('nombre');
            $permisos = $user->roles->flatMap(function ($rol) {
                return $rol->permisos;
            })->pluck('slug')->unique()->values();

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $roles,
                        'permisos' => $permisos,
                    ],
                ],
            ]);
        }

        $email = $request->input('email');
        AuditLogger::log('LOGIN_FALLIDO', 'warning', "Intento de login fallido para email: {$email}");

        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas',
        ], 401);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            AuditLogger::log('LOGOUT', 'info', "Usuario {$user->email} cerró sesión.");
            // @phpstan-ignore-next-line
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $roles = $user->roles->pluck('nombre');
        $permisos = $user->roles->flatMap(function ($rol) {
            return $rol->permisos;
        })->pluck('slug')->unique()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'permisos' => $permisos,
            ],
        ]);
    }
}
