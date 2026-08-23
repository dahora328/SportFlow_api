<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        if (!$user->is_admin || $user->enterprise_id === null) {
            return response()->json(['error' => 'Acesso negado. Apenas gestores podem listar usuários.'], 403);
        }

        $query = \App\Models\User::where('enterprise_id', $user->enterprise_id);
        
        if ($request->has('all')) {
            return response()->json(['data' => $query->get()]);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $admin = auth('api')->user();
        if (!$admin || !$admin->is_admin || $admin->enterprise_id === null) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'is_admin' => 'sometimes|boolean',
        ]);

        $data['enterprise_id'] = $admin->enterprise_id;
        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        
        if (!isset($data['is_admin'])) {
            $data['is_admin'] = false;
        }

        $newUser = \App\Models\User::create($data);

        return response()->json([
            'message' => 'Usuário criado com sucesso!',
            'user' => $newUser
        ], 201);
    }

    public function show(string $id)
    {
        $admin = auth('api')->user();
        $targetUser = \App\Models\User::find($id);

        if (!$targetUser) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (!$admin || !$admin->is_admin || $admin->enterprise_id !== $targetUser->enterprise_id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        return response()->json($targetUser);
    }

    public function update(Request $request, string $id)
    {
        $admin = auth('api')->user();
        $targetUser = \App\Models\User::find($id);

        if (!$targetUser) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (!$admin || !$admin->is_admin || $admin->enterprise_id !== $targetUser->enterprise_id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $targetUser->id,
            'password' => 'nullable|string|min:6',
            'is_admin' => 'sometimes|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $targetUser->update($data);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $targetUser
        ], 200);
    }

    public function destroy(string $id)
    {
        $admin = auth('api')->user();
        $targetUser = \App\Models\User::find($id);

        if (!$targetUser) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (!$admin || !$admin->is_admin || $admin->enterprise_id !== $targetUser->enterprise_id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        
        if ($admin->id == $targetUser->id) {
            return response()->json(['error' => 'Não é possível excluir a si mesmo.'], 400);
        }

        $targetUser->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso!'
        ]);
    }
}
