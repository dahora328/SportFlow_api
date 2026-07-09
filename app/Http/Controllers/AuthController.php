<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Criar novo usuário — rota protegida (somente admins podem criar usuários)
     *
     * Regras:
     * - Super Admin (is_admin=true, enterprise_id=null): pode criar qualquer usuário,
     *   passando o enterprise_id desejado no body.
     * - Gestor da empresa (is_admin=true, enterprise_id != null): pode criar usuários,
     *   mas o enterprise_id é injetado automaticamente (o seu próprio).
     * - Funcionário comum (is_admin=false): acesso negado.
     */
    public function register(Request $request)
    {
        $logado = auth('api')->user();

        // Somente admins (Super Admin ou Gestor) podem criar usuários
        if (!$logado || !$logado->is_admin) {
            return response()->json(['error' => 'Acesso negado. Apenas administradores podem criar usuários.'], 403);
        }

        $request->validate([
<<<<<<< HEAD
            'name'     => 'required|string|max:255',
=======
            'name'     => 'required',
>>>>>>> create_organizations
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

<<<<<<< HEAD
        // Super Admin (sem empresa) passa enterprise_id no body
        // Gestor da empresa usa o próprio enterprise_id automaticamente
        $enterpriseId = ($logado->enterprise_id === null)
            ? $request->enterprise_id
            : $logado->enterprise_id;
=======
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'enterprise_id' => env('DEFAULT_ENTERPRISE_ID', 1), // Futuramente arrumar para permitir várias empresas.
        ]);
>>>>>>> create_organizations

        // Super Admin pode definir is_admin; Gestor sempre cria funcionário comum
        $isAdmin = ($logado->enterprise_id === null)
            ? (bool) ($request->is_admin ?? false)
            : false;

        $novoUsuario = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'is_admin'      => $isAdmin,
            'enterprise_id' => $enterpriseId,
        ]);

        return response()->json([
<<<<<<< HEAD
            'message' => 'Usuário criado com sucesso!',
            'user'    => $novoUsuario,
=======
            'user'       => $user,
            'token'      => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
>>>>>>> create_organizations
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Refresh Token

        $user              = auth()->user();
        $plainRefreshToken = Str::random(60);

        $user->refresh_token            = hash('sha256', $plainRefreshToken);
        $user->refresh_token_expires_at = now()->addDays(7);
        $user->save();

        return response()->json([
            'user_id'       => $user->id,
            'access_token'  => $token,
            'refresh_token' => $plainRefreshToken, // ← token puro enviado ao front
        ]);
    }

    /**
     * Refresh Token (gera novo access_token)
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->refresh_token;

        if (! $refreshToken) {
            return response()->json(['error' => 'Refresh token requerido'], 400);
        }

        $hash = hash('sha256', $refreshToken);
        $user = User::where('refresh_token', $hash)->first();

        if (! $user) {
            return response()->json(['error' => 'Refresh token inválido'], 401);
        }

        if ($user->refresh_token_expires_at < now()) {
            return response()->json(['error' => 'Refresh token expirado'], 401);
        }

        // Gera novo access token
        $newAccessToken = auth()->login($user);

        return response()->json([
            'access_token' => $newAccessToken,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
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

    /**
     * Atualizar dados do usuário logado
     */
    public function updateUser(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->fill($request->only('name', 'email'));
        $user->save();

        return response()->json(['message' => 'Perfil atualizado com sucesso!', 'user' => $user]);
    }
}
