<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CepLookupTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name'     => 'CEP User',
            'email'    => 'cep@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
    }

    public function test_resolves_cep_via_viacep(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'logradouro' => 'Praça da Sé',
                'bairro'     => 'Sé',
                'localidade' => 'São Paulo',
                'uf'         => 'SP',
            ], 200),
        ]);

        $response = $this->actingAs($this->user(), 'api')->getJson('/api/cep/01001000');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => [
                    'address'      => 'Praça da Sé',
                    'neighborhood' => 'Sé',
                    'city'         => 'São Paulo',
                    'state'        => 'SP',
                ],
            ]);
    }

    public function test_falls_back_to_brasilapi_when_viacep_is_down(): void
    {
        Http::fake([
            'viacep.com.br/*'    => fn () => throw new ConnectionException('timeout'),
            'brasilapi.com.br/*' => Http::response([
                'street'       => 'Praça da Sé',
                'neighborhood' => 'Sé',
                'city'         => 'São Paulo',
                'state'        => 'SP',
            ], 200),
        ]);

        $response = $this->actingAs($this->user(), 'api')->getJson('/api/cep/01001000');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['address' => 'Praça da Sé', 'state' => 'SP'],
            ]);
    }

    public function test_returns_404_when_cep_does_not_exist(): void
    {
        // ViaCEP responde 200 com {"erro": true} para CEP inexistente.
        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
        ]);

        $response = $this->actingAs($this->user(), 'api')->getJson('/api/cep/99999999');

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_returns_503_when_both_providers_are_down(): void
    {
        Http::fake([
            'viacep.com.br/*'    => fn () => throw new ConnectionException('timeout'),
            'brasilapi.com.br/*' => fn () => throw new ConnectionException('timeout'),
        ]);

        $response = $this->actingAs($this->user(), 'api')->getJson('/api/cep/01001000');

        $response->assertStatus(503)->assertJson(['success' => false]);
    }

    public function test_rejects_invalid_cep_format(): void
    {
        $response = $this->actingAs($this->user(), 'api')->getJson('/api/cep/123');

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_caches_resolved_cep_and_skips_second_external_call(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'logradouro' => 'Praça da Sé',
                'bairro'     => 'Sé',
                'localidade' => 'São Paulo',
                'uf'         => 'SP',
            ], 200),
        ]);

        $user = $this->user();
        $this->actingAs($user, 'api')->getJson('/api/cep/01001000')->assertOk();
        $this->actingAs($user, 'api')->getJson('/api/cep/01001000')->assertOk();

        // A segunda busca deve vir do cache, sem nova chamada externa.
        Http::assertSentCount(1);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/cep/01001000')->assertStatus(401);
    }
}
