<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAthletesRequest;
use App\Models\Athletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $data['owner_id'] = auth('api')->user()->id;
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
    public function update(Request $request, int $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $athlete = Athletes::findOrFail($id);
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
