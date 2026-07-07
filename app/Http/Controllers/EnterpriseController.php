<?php

namespace App\Http\Controllers;

use App\Models\Enterprise;
use Illuminate\Http\Request;

class EnterpriseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();
        
        // Pega a empresa vinculada ao usuário logado através do enterprise_id da tabela users
        if ($user && $user->enterprise_id) {
            $enterprises = Enterprise::where('id', $user->enterprise_id)->get();
        } else {
            $enterprises = collect([]); // Retorna array vazio se o usuário não tiver empresa
        }
        
        return response()->json($enterprises);
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

        if (!isset($data['owner_name'])) {
            $data['owner_name'] = $user->name;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $enterprise = Enterprise::create($data);
        
        $user->enterprise_id = $enterprise->id;
        $user->save();

        return response()->json([
            'message' => 'Empresa criada com sucesso!',
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
            'message' => 'Empresa atualizada com sucesso!',
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
