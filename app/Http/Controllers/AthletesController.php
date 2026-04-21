<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreAthletesRequest;
use App\Http\Requests\UpdateAthletesRequest;
use App\Http\Resources\AthletesResource;
use App\Models\Athlete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AthletesController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all or search by full_name athletes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Athlete::query();

        if ($request->filled('search')) {
            $search = substr($request->search, 0, 50); // limita input
            $query->where('full_name', 'like', "%{$search}%");
        }

        // Ordenação segura (whitelist)
        $allowedSorts = ['full_name', 'created_at'];

        $sort = $request->filled('sort') && in_array($request->sort, $allowedSorts) ? $request->sort : 'created_at';
        $direction = $request->filled('direction') && in_array(strtolower($request->direction), ['asc', 'desc']) ? strtolower($request->direction) : 'desc';

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        $perPage = min($request->filled('per_page') ? (int) $request->per_page : 15, 100);

        $athletes = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Atletas listados com sucesso',
            'data' => AthletesResource::collection($athletes)
        ]);
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
        $this->authorize('create', Athlete::class);

        try {
            $user = auth()->user();

            $athlete = DB::transaction(function () use ($request, $user) {

                $data = $request->validated();
                $data['owner_id'] = $user->id;

                if ($request->hasFile('photo_path')) {
                    $data['photo_path'] = $request
                        ->file('photo_path')
                        ->store('athletes', 'public');
                }

                return Athlete::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Atleta criado com sucesso!',
                'data' => $athlete,
            ], 201);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno no servidor',
            ], 500);
        }
    }

    /**
     * Get athlete by ID
     */
    public function show(Athlete $athlete): JsonResponse
    {
        $this->authorize('view', $athlete);

        return response()->json([
            'success' => true,
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
        $this->authorize('update', $athlete);

        try {
            $updatedAthlete = DB::transaction(function () use ($request, $athlete, &$changedFields) {

                $data = $request->validated();

                // Upload de imagem
                if ($request->hasFile('photo_path')) {

                    // Remove imagem antiga com segurança
                    if ($athlete->photo_path && Storage::disk('public')->exists($athlete->photo_path)) {
                        Storage::disk('public')->delete($athlete->photo_path);
                    }

                    $data['photo_path'] = $request
                        ->file('photo_path')
                        ->store('athletes', 'public');
                }

                $athlete->fill($data);

                // Captura alterações antes de salvar
                $changedFields = array_keys($athlete->getDirty());

                if (empty($changedFields)) {
                    return $athlete;
                }

                $athlete->save();

                return $athlete->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => empty($changedFields)
                    ? 'Nenhum dado foi alterado.'
                    : 'Atleta atualizado com sucesso!',
                'data' => $updatedAthlete,
                'changed_fields' => $changedFields
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Erro ao atualizar atleta', [
                'error' => $e->getMessage(),
                'athlete_id' => $athlete->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno no servidor',
            ], 500);
        }
    }

    /**
     * Remove athlete by ID
     */
    public function destroy(Athlete $athlete): JsonResponse
    {
        $this->authorize('delete', $athlete);

        try {
            DB::transaction(function () use ($athlete) {

                // Remove arquivo se existir
                if ($athlete->photo_path && Storage::disk('public')->exists($athlete->photo_path)) {
                    Storage::disk('public')->delete($athlete->photo_path);
                }

                $athlete->delete();
            });

            return response()->json(null, 204);
        } catch (\Throwable $e) {

            Log::error('Erro ao excluir atleta', [
                'error' => $e->getMessage(),
                'athlete_id' => $athlete->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno no servidor',
            ], 500);
        }
    }
}