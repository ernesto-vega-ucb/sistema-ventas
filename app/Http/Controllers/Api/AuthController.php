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
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (Auth::attempt($credentials)) {
            /** @var User $user */
            $user = Auth::user();

            if (! $user->getAttribute('estado')) {
                Auth::logout();

                return response()->json(['success' => false, 'message' => 'Cuenta inactiva'], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userEmail = $user->getAttribute('email');
            $userName = $user->getAttribute('name');
            $userId = $user->getAttribute('id');

            AuditLogger::login("Usuario {$userEmail} inició sesión.");

            $roles = $user->roles()->pluck('nombre');
            $permisos = $roles->flatMap(function ($rol) {
                $permisosRelacion = $rol->getAttribute('permisos');

                return $permisosRelacion ? $permisosRelacion->pluck('slug') : collect();
            })->unique()->values()->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userId,
                        'name' => $userName,
                        'email' => $userEmail,
                        'roles' => $roles,
                        'permisos' => $permisos,
                    ],
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $email = $user->getAttribute('email');
            AuditLogger::log('LOGOUT', 'info', "Usuario {$email} cerró sesión.");
            $user->tokens()->delete(); // Más limpio para PHPStan
        }

        return response()->json(['success' => true]);
    }

    public function user(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->getAttribute('id'),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'roles' => $user->roles()->pluck('nombre'),
                'permisos' => $user->roles()->with('permisos')->get()->flatMap(fn ($r) => $r->permisos)->pluck('slug')->unique()->values(),
            ],
        ]);
    }
}
