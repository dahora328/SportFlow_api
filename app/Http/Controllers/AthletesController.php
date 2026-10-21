<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exports\AthletesExport;
use App\Exports\AthletesImportTemplateExport;
use App\Http\Requests\StoreAthletesRequest;
use App\Http\Requests\UpdateAthletesRequest;
use App\Http\Resources\AthleteListResource;
use App\Http\Resources\AthletesResource;
use App\Imports\AthletesImport;
use App\Models\Athlete;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AthletesController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all or search by full_name athletes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Busca só as colunas usadas na listagem
            $query = Athlete::query()->select(AthleteListResource::COLUMNS);

            if ($request->filled('search')) {
                $search = mb_substr((string) $request->search, 0, 50); // limita input
                // LOWER(...) LIKE funciona em qualquer driver (pgsql, mysql, sqlite),
                // ao contrário do ILIKE que é exclusivo do PostgreSQL.
                $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . mb_strtolower($search) . '%']);
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

            // paginate() expõe total e last_page (custo de 1 COUNT, irrelevante
            // com os índices já criados). O Laravel resolve ?page= automaticamente.
            $athletes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Atletas listados com sucesso',
                // items() devolve só os modelos da página atual; os metadados
                // vão em 'meta' para o front saber se há próxima página e o total.
                'data' => AthleteListResource::collection($athletes->items()),
                'meta' => [
                    'current_page' => $athletes->currentPage(),
                    'last_page'    => $athletes->lastPage(),
                    'per_page'     => $athletes->perPage(),
                    'total'        => $athletes->total(),
                    'from'         => $athletes->firstItem(),
                    'to'           => $athletes->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar/buscar atletas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno no servidor',
            ], 500);
        }
    }

    /**
     * Exporta a lista de atletas em Excel (.xlsx).
     *
     * Regras:
     *  - Só Gestor / Super Admin (is_admin) podem exportar (LGPD: o arquivo
     *    leva CPF, endereço e telefones para fora do sistema).
     *  - Respeita o escopo da empresa (global scope do model) e o filtro de
     *    busca ativo, exportando exatamente o que o usuário está vendo.
     *  - Registra em log de auditoria quem exportou e quantos registros.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $user = auth()->user();

        if (!$user || !$user->is_admin) {
            abort(403, 'Você não tem permissão para exportar atletas.');
        }

        $search = $request->filled('search')
            ? mb_substr((string) $request->search, 0, 50)
            : null;

        Log::channel('security')->info('athlete.exported', [
            'actor_id' => $user->id,
            'search'   => $search,
            'ip'       => $request->ip(),
        ]);

        $fileName = 'atletas_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AthletesExport($search), $fileName);
    }

    /**
     * Baixa o modelo (template) oficial de importação em .xlsx, já com os
     * cabeçalhos na ordem correta, uma linha de exemplo e listas suspensas
     * para gênero, estado civil, posição e UF.
     */
    public function importTemplate(): BinaryFileResponse
    {
        $user = auth()->user();

        if (!$user || !$user->is_admin) {
            abort(403, 'Você não tem permissão para importar atletas.');
        }

        return Excel::download(new AthletesImportTemplateExport(), 'modelo_importacao_atletas.xlsx');
    }

    /**
     * Importa atletas a partir de um arquivo .csv ou .xlsx seguindo o template.
     *
     * Valida linha a linha (campos obrigatórios, CPF/CNPJ e duplicados) e
     * devolve um resumo: quantos foram importados e os erros por linha.
     */
    public function import(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para importar atletas.',
            ], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'Selecione um arquivo para importar.',
            'file.mimes'    => 'O arquivo deve ser .csv ou .xlsx.',
            'file.max'      => 'O arquivo não pode ter mais de 5 MB.',
        ]);

        try {
            $import = new AthletesImport();
            Excel::import($import, $request->file('file'));

            Log::channel('security')->info('athlete.imported', [
                'actor_id' => $user->id,
                'imported' => $import->importedCount(),
                'skipped'  => count($import->errors()),
                'ip'       => $request->ip(),
            ]);

            return response()->json([
                'success'  => true,
                'message'  => $import->importedCount() > 0
                    ? "{$import->importedCount()} atleta(s) importado(s) com sucesso."
                    : 'Nenhum atleta foi importado.',
                'imported' => $import->importedCount(),
                'skipped'  => count($import->errors()),
                'errors'   => $import->errors(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao importar atletas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível ler o arquivo. Verifique se ele segue o modelo.',
            ], 422);
        }
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

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

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
                'data' => new AthletesResource($athlete),
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
            'data' => new AthletesResource($athlete),
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
                'data' => new AthletesResource($updatedAthlete),
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

            Log::channel('security')->info('athlete.deleted', [
                'actor_id'   => auth()->id(),
                'athlete_id' => $athlete->id,
                'ip'         => request()->ip(),
            ]);

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
