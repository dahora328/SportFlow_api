<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca de endereço por CEP com estratégia de fallback.
 *
 * Provedor principal: ViaCEP (mais estável).
 * Fallback: BrasilAPI (quando o ViaCEP está fora do ar).
 *
 * Toda a lógica de provedores vive aqui, de propósito: trocar de provedor,
 * inverter a ordem, acrescentar um terceiro ou ajustar o cache é mudança
 * em um lugar só, sem tocar no front.
 */
class CepLookupService
{
    /** Dias que um CEP resolvido fica em cache (endereços quase não mudam). */
    private const CACHE_TTL_DAYS = 30;

    /** Timeout (segundos) de cada chamada externa. */
    private const TIMEOUT = 5;

    /**
     * Resolve um CEP para um endereço normalizado.
     *
     * @return array{address:string,neighborhood:string,city:string,state:string}|null
     *         Endereço normalizado, ou null quando o CEP não existe em nenhum provedor.
     *
     * @throws \RuntimeException Quando os dois provedores estão indisponíveis.
     */
    public function lookup(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        $cacheKey = "cep:{$cep}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->resolve($cep);

        // Só cacheia acerto: "não encontrado" e indisponibilidade não ficam presos.
        if ($data !== null) {
            Cache::put($cacheKey, $data, now()->addDays(self::CACHE_TTL_DAYS));
        }

        return $data;
    }

    /**
     * @return array{address:string,neighborhood:string,city:string,state:string}|null
     *
     * @throws \RuntimeException
     */
    private function resolve(string $cep): ?array
    {
        // 1) Provedor principal: ViaCEP.
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->successful()) {
                $body = $response->json();

                // O ViaCEP responde 200 com {"erro": true} quando o CEP não existe.
                if (($body['erro'] ?? false)) {
                    return null;
                }

                return [
                    'address'      => $body['logradouro'] ?? '',
                    'neighborhood' => $body['bairro'] ?? '',
                    'city'         => $body['localidade'] ?? '',
                    'state'        => $body['uf'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('ViaCEP indisponível; tentando fallback BrasilAPI', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Fallback: BrasilAPI (v2 agrega várias fontes internamente).
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'address'      => $body['street'] ?? '',
                    'neighborhood' => $body['neighborhood'] ?? '',
                    'city'         => $body['city'] ?? '',
                    'state'        => $body['state'] ?? '',
                ];
            }

            // 404 na BrasilAPI = CEP não encontrado em nenhuma base.
            if ($response->status() === 404) {
                return null;
            }
        } catch (\Throwable $e) {
            Log::error('BrasilAPI também indisponível na busca de CEP', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
            ]);
        }

        // Os dois provedores falharam por indisponibilidade (não por "não encontrado").
        throw new \RuntimeException('Serviço de CEP indisponível');
    }
}
