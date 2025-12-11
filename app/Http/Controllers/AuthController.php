<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Refresh Token

        $user = auth()->user();
        $plainRefreshToken = Str::random(60);

        $user->refresh_token = hash('sha256', $plainRefreshToken);
        $user->refresh_token_expires_at = now()->addDays(7);
        $user->save();

        return response()->json([
            'user_id' => $user->id,
            'access_token' => $token,
            'refresh_token' => $plainRefreshToken, // ← token puro enviado ao front
        ]);
    }

    /**
     * Refresh Token (gera novo access_token)
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->refresh_token;

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token requerido'], 400);
        }

        $hash = hash('sha256', $refreshToken);
        $user = User::where('refresh_token', $hash)->first();

        if (!$user) {
            return response()->json(['error' => 'Refresh token inválido'], 401);
        }

        if ($user->refresh_token_expires_at < now()) {
            return response()->json(['error' => 'Refresh token expirado'], 401);
        }

        // Gera novo access token
        $newAccessToken = auth()->login($user);

        return response()->json([
            'access_token' => $newAccessToken,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }


    /**
     * Logout
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * Obter usuário logado
     */
    public function me()
    {
        return response()->json(auth()->user());
    }
}
