<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAthletesRequest;
use App\Http\Requests\UpdateAthletesRequest;
use App\Models\Athlete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AthletesController extends Controller
{
    /**
     * Get all athletes
     */
    public function index(): JsonResponse
    {
        $athletes = Athlete::all();
        return response()->json([
            'success' => true,
            'message' => 'Atletas lsitados com sucesso!',
            'data' => $athletes
        ], 200);
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
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        try {
            $athlete = DB::transaction(function () use ($request, $user) {

                $data = $request->validated();
                $data['owner_id'] = $user->id;

                // Upload
                if ($request->hasFile('photo_path')) {
                    $path = $request->file('photo_path')->store('athletes', 'public');
                    $data['photo_path'] = $path;
                }

                return Athlete::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Atleta criado com sucesso!',
                'data' => $athlete,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar atleta',
            ], 500);
        }
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
                'success' => false,
                'message' => 'ID inválido: ' . $id,
            ], 400);
        }

        $athlete = Athlete::find($numericId);

        if (!$athlete) {
            return response()->json([
                'success' => false,
                'message' => 'Atleta não encontrado com ID: ' . $numericId,
            ], 404);
        }

        return response()->json([
            'sucess' => true,
            'message' => 'Atleta encontrado com sucesso!',
            'data' => $athlete,
        ], 200);
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
            $validatedData = $request->validated();

            DB::beginTransaction();

            // TRATAR UPLOAD DE IMAGEM
            if ($request->hasFile('photo_path')) {

                // Remove imagem antiga (se existir)
                if (!empty($athlete->photo_path)) {
                    Storage::disk('public')->delete($athlete->photo_path);
                }

                // Salva nova imagem
                $validatedData['photo_path'] = $request
                    ->file('photo_path')
                    ->store('athletes', 'public');
            }

            // Preenche os dados
            $athlete->fill($validatedData);

            if (!$athlete->isDirty()) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'message' => 'Nenhum dado foi alterado.',
                    'athlete' => $athlete
                ], 200);
            }

            $athlete->save();

            // Captura campos alterados ANTES do refresh
            $changedFields = array_keys($athlete->getChanges());

            $athlete->refresh();

            DB::commit();

            return response()->json([
                'message' => 'Atleta atualizado com sucesso!',
                'athlete' => $athlete,
                'changed_fields' => $changedFields
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Erro ao atualizar atleta', [
                'error' => $e->getMessage(),
                'athlete_id' => $athlete->id ?? null
            ]);

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
                'success' => false,
                'message' => 'Atleta não encontrado',
            ], 404);
        }

        //Remove a foto do storage se existir
        if ($athlete->photo_path) {
            Storage::disk('public')->delete($athlete->photo_path);
        }

        $athlete->delete();
        return response()->json([
            'sucess' => true,
            'message' => 'Atleta excluído com sucesso!',
            'data' => null
        ], 204);
    }
}
