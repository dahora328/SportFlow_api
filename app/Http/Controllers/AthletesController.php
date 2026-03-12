<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAthletesRequest;
use App\Http\Requests\UpdateAthletesRequest;
use App\Models\Athlete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AthletesController extends Controller
{
    /**
     * Get all athletes
     */
    public function index(): JsonResponse
    {
        $athletes = Athlete::all();
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
     * Create a new athlete
     */
    public function store(StoreAthletesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        $data['owner_id'] = $user->id;
        $athlete = Athlete::create($data);
        // dd($athlete);
        return response()->json([
            'message' => 'Atleta criado com sucesso!',
            'athlete' => $athlete
        ], 201);
    }

    /**
     * Get athletes by name (search)
     */

    public function searchByName(Request $request): JsonResponse
    {
        $name = $request->query('name');

        if (!$name) {
            return response()->json([
                'message' => 'Informe o nome para pesquisa (parâmetro name).'
            ], 422);
        }

        $athletes = Athlete::where('full_name', 'like', "%{$name}%")->get();

        if ($athletes->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum atleta encontrado com o nome: ' . $name
            ], 404);
        }

        return response()->json($athletes, 200);
    }

    /**
     * Get athlete by ID
     */
    public function show(int $id): JsonResponse
    {
        // Converte string para int se possível
        $numericId = (int) $id;

        if ($numericId <= 0) {
            return response()->json([
                'message' => 'ID inválido: ' . $id
            ], 400);
        }

        $athlete = Athlete::find($numericId);

        if (!$athlete) {
            return response()->json([
                'message' => 'Atleta não encontrado com ID: ' . $numericId
            ], 404);
        }

        return response()->json($athlete, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update athlete by ID
     */
    public function update(UpdateAthletesRequest $request, Athlete $athlete): JsonResponse
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
     * Remove athlete by ID
     */
    public function destroy(int $id)
    {
        $athlete = Athlete::find($id);
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
