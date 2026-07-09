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
    public function index()
    {
        $user = auth('api')->user();

        // Super Admin vê todas as empresas (necessário para o painel admin)
        if ($user && $user->is_admin && $user->enterprise_id === null) {
            return response()->json(Enterprise::orderBy('name')->get());
        }

        // Gestor ou Funcionário vê apenas a própria empresa
        if ($user && $user->enterprise_id) {
            return response()->json(Enterprise::where('id', $user->enterprise_id)->get());
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
    public function store(Request $request)
    {
        $data = $request->all();
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
        $enterprise = Enterprise::find($id);
        if (!$enterprise) {
            return response()->json([
                'error' => 'Empresa não encontrada'
            ], 404);
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
        $data = $request->all();

        \Illuminate\Support\Facades\Log::info('Update enterprise called', [
            'has_logo' => $request->hasFile('logo'),
            'logo_file' => $request->file('logo'),
            'data' => $data
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
        $enterprise = Enterprise::find($id);
        if (!$enterprise) {
            return response()->json([
                'error' => 'Empresa não encontrada'
            ], 404);
        }
        $enterprise->delete();
        return response()->json([
            'message' => 'Empresa removida com sucesso!'
        ]);
    }
}
