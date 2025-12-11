<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAthletesRequest;
use App\Http\Requests\UpdateAthletesRequest;
use App\Models\Athletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AthletesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $athletes = Athletes::all();
        return response()->json($athletes);
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
    public function store(StoreAthletesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        $data['owner_id'] = $user->id;
        $athlete = Athletes::create($data);
        // dd($athlete);
        return response()->json([
            'message' => 'Atleta criado com sucesso!',
            'athlete' => $athlete
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $athlete = Athletes::findOrFail($id);
        if (!$athlete) {
            return response()->json([
                'message' => 'Atleta não encontrado'
            ], 404);
        }
        return response()->json($athlete);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAthletesRequest $request, Athletes $athlete): JsonResponse
    {
        try {
            // Os dados já foram validados automaticamente pelo FormRequest
            $validatedData = $request->validated();

            // Atualiza apenas os campos que foram enviados na request
            $athlete->fill($validatedData);

            // Salva as alterações
            if ($athlete->isDirty()) {
                $athlete->save();
                $athlete->refresh();

                return response()->json([
                    'message' => 'Atleta atualizado com sucesso!',
                    'athlete' => $athlete,
                    'changed_fields' => array_keys($athlete->getChanges())
                ]);
            } else {
                return response()->json([
                    'message' => 'Nenhum dado foi alterado.',
                    'athlete' => $athlete
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar atleta: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro interno ao atualizar atleta',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $athlete = Athletes::find($id);
        if (!$athlete) {
            return response()->json([
                'message' => 'Atleta não encontrado'
            ], 404);
        }
        $athlete->delete();
        return response()->json([
            'message' => 'Atleta excluído com sucesso!'
        ]);
    }
}
