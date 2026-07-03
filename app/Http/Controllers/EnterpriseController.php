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
        // NOTA: Como você não tem o form request validado e a lógica exata de campos requeridos aqui, 
        // e 'owner_id' não existe na tabela enterprise, vamos apenas usar os dados vindos no request.
        // Se a validação for necessária, crie um FormRequest.
        $data = $request->all();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Criar a empresa
        $enterprise = Enterprise::create($data);
        
        // Se desejar já vincular o usuário que criou a empresa recém-criada, você pode fazer:
        // $user->enterprise_id = $enterprise->id;
        // $user->save();

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
        //
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
