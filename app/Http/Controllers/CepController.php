<?php

namespace App\Http\Controllers;

use App\Services\CepLookupService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de busca de endereço por CEP.
 *
 * O front chama este endpoint (autenticado) ao preencher o CEP nos cadastros
 * de atleta e empresa; a escolha de provedor e o fallback ficam no serviço.
 */
class CepController extends Controller
{
    public function __construct(private readonly CepLookupService $cepLookup)
    {
    }

    public function show(string $cep): JsonResponse
    {
        $digits = preg_replace('/\D/', '', $cep);

        if (strlen($digits) !== 8) {
            return response()->json([
                'success' => false,
                'message' => __('cep.invalid'),
            ], 422);
        }

        try {
            $data = $this->cepLookup->lookup($digits);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('cep.unavailable'),
            ], 503);
        }

        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => __('cep.not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('cep.found'),
            'data'    => $data,
        ]);
    }
}
