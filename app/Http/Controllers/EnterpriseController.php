<?php

namespace App\Http\Controllers;

use App\Models\Enterprise;
use Illuminate\Http\Request;

class EnterpriseController extends Controller
{
    /**
     * Listar empresas.
     *
     * - Super Admin (is_admin=true, enterprise_id=null): retorna todas as empresas.
     * - Gestor / Funcionário (enterprise_id != null): retorna apenas a empresa vinculada.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        // Super Admin vê todas as empresas (necessário para o painel admin)
        if ($user && $user->is_admin && $user->enterprise_id === null) {
            $query = Enterprise::orderBy('name');
            return response()->json($request->has('all') ? $query->get() : $query->paginate(15));
        }

        // Gestor ou Funcionário vê apenas a própria empresa
        if ($user && $user->enterprise_id) {
            $query = Enterprise::where('id', $user->enterprise_id);
            return response()->json($request->has('all') ? $query->get() : $query->paginate(15));
        }

        return response()->json([]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreEnterpriseRequest $request)
    {
        $data = $request->validated();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Somente o Super Admin pode criar empresas
        if (!$user->is_admin || $user->enterprise_id !== null) {
            return response()->json(['error' => 'Acesso negado. Apenas o Super Admin pode cadastrar empresas.'], 403);
        }

        if (!isset($data['owner_name'])) {
            $data['owner_name'] = $user->name;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $enterprise = Enterprise::create($data);
        return response()->json([
            'message'    => 'Empresa criada com sucesso!',
            'enterprise' => $enterprise
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        
        $enterprise = Enterprise::find($id);
        if (!$enterprise) {
            return response()->json([
                'error' => 'Empresa não encontrada'
            ], 404);
        }

        if (!$user->is_admin && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        if ($user->is_admin && $user->enterprise_id !== null && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        return response()->json($enterprise, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Enterprise $enterprise)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Enterprise $enterprise)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Verifica permissão (super admin ou gestor da própria empresa)
        if (!$user->is_admin && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        if ($user->is_admin && $user->enterprise_id !== null && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'social_reason' =>  'sometimes|string|max:255',
            'fantasy_name' => 'sometimes|string|max:255',
            'owner_name' => 'sometimes|string|max:255',
            'document' => 'sometimes|string|max:255',
            'foundation_date' => 'sometimes|date',
            'IE' => 'nullable|string|max:255',
            'address' => 'sometimes|string|max:255',
            'number' => 'sometimes|string|max:255',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'zip_code' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:enterprises,email,' . $enterprise->id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $enterprise->update($data);

        return response()->json([
            'message'    => 'Empresa atualizada com sucesso!',
            'enterprise' => $enterprise
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        
        $enterprise = Enterprise::find($id);
        if (!$enterprise) {
            return response()->json([
                'error' => 'Empresa não encontrada'
            ], 404);
        }

        if (!$user->is_admin && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        if ($user->is_admin && $user->enterprise_id !== null && $user->enterprise_id !== $enterprise->id) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }
        $enterprise->delete();
        return response()->json([
            'message' => 'Empresa removida com sucesso!'
        ]);
    }
}
