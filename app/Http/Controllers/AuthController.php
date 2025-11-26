<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str as SupportStr;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'user'          => $user,
            'message' => 'User successfully registered',
            'authorization' => [
                'token'      => $token,
                'type'       => 'bearer',
                'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        $refresh_token = SupportStr::random(60);

        $user = auth()->user();
        $user->refresh_token = SupportStr::random(60);
        $user->refresh_token_expires_at = now()->addDays(7);
        $user->save();

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) JWTAuth::factory()->getTTL() * 60,
            'refresh_token' => $user->refresh_token,
        ]);
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->refresh_token;

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token requerido'], 400);
        }

        // buscar hash
        $hash = hash('sha256', $refreshToken);

        $user = User::where('refresh_token', $hash)->first();

        if (!$user) {
            return response()->json(['error' => 'Refresh token inválido'], 401);
        }

        if ($user->refresh_token_expires_at < now()) {
            return response()->json(['error' => 'Refresh token expirado'], 401);
        }

        // tudo certo → gerar novo access token
        $newAccessToken = auth()->login($user);

        return response()->json([
            'access_token' => $newAccessToken,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getUser()
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to fetch user profile'], 500);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $user = Auth::user();
            $user->update($request->only(['name', 'email']));
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }
}
