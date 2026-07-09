<?php

namespace App\Http\Controllers;

use App\Models\Enterprise;
use Illuminate\Http\Request;

class EnterpriseController extends Controller
{
    /**
<<<<<<< HEAD
     * Listar empresas.
     *
     * - Super Admin (is_admin=true, enterprise_id=null): retorna todas as empresas.
     * - Gestor / Funcionário (enterprise_id != null): retorna apenas a empresa vinculada.
=======
     * Display a listing of the resource.
>>>>>>> create_organizations
     */
    public function index()
    {
        $user = auth('api')->user();
<<<<<<< HEAD

        // Super Admin vê todas as empresas (necessário para o painel admin)
        if ($user && $user->is_admin && $user->enterprise_id === null) {
            return response()->json(Enterprise::orderBy('name')->get());
        }

        // Gestor ou Funcionário vê apenas a própria empresa
        if ($user && $user->enterprise_id) {
            return response()->json(Enterprise::where('id', $user->enterprise_id)->get());
        }

        return response()->json([]);
=======
        
        // Pega a empresa vinculada ao usuário logado através do enterprise_id da tabela users
        if ($user && $user->enterprise_id) {
            $enterprises = Enterprise::where('id', $user->enterprise_id)->get();
        } else {
            $enterprises = collect([]); // Retorna array vazio se o usuário não tiver empresa
        }
        
        return response()->json($enterprises);
>>>>>>> create_organizations
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

<<<<<<< HEAD
        // Somente o Super Admin pode criar empresas
        if (!$user->is_admin || $user->enterprise_id !== null) {
            return response()->json(['error' => 'Acesso negado. Apenas o Super Admin pode cadastrar empresas.'], 403);
        }

=======
>>>>>>> create_organizations
        if (!isset($data['owner_name'])) {
            $data['owner_name'] = $user->name;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $enterprise = Enterprise::create($data);
<<<<<<< HEAD

        return response()->json([
            'message'    => 'Empresa criada com sucesso!',
=======
        
        $user->enterprise_id = $enterprise->id;
        $user->save();

        return response()->json([
            'message' => 'Empresa criada com sucesso!',
>>>>>>> create_organizations
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
<<<<<<< HEAD
            'message'    => 'Empresa atualizada com sucesso!',
=======
            'message' => 'Empresa atualizada com sucesso!',
>>>>>>> create_organizations
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
