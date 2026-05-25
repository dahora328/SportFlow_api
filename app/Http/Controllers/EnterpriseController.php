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
        $enterprises = Enterprise::all();
        return response()->json($enterprises, 200);
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
        $data = $request->validated();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        $data['owner_id'] = $user->id;

        $enterprise = Enterprise::create($data);
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
