<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;

class EnterpriseTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_enterprise()
    {
        $superAdmin = User::factory()->create([
            'is_admin' => true,
            'enterprise_id' => null
        ]);

        $response = $this->actingAs($superAdmin, 'api')->postJson('/api/enterprises', [
            'name' => 'Empresa Teste',
            'social_reason' => 'Empresa Teste LTDA',
            'fantasy_name' => 'Teste Fantasia',
            'owner_name' => 'Dono Teste',
            'document' => '00000000000191', // Valid CNPJ (Banco do Brasil)
            'foundation_date' => '2020-01-01',
            'IE' => '123456789',
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'city' => 'Cidade Teste',
            'state' => 'SP',
            'zip_code' => '12345678',
            'phone' => '11999999999',
            'email' => 'contato@teste.com',
            'active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('enterprises', ['email' => 'contato@teste.com']);
    }

    public function test_enterprise_persists_address_fields_from_cep()
    {
        $superAdmin = User::factory()->create([
            'is_admin' => true,
            'enterprise_id' => null,
        ]);

        // Campos de endereço como viriam preenchidos pela busca de CEP (ViaCEP).
        $response = $this->actingAs($superAdmin, 'api')->postJson('/api/enterprises', [
            'name' => 'Empresa Endereco',
            'social_reason' => 'Empresa Endereco LTDA',
            'fantasy_name' => 'Endereco Fantasia',
            'owner_name' => 'Dono Teste',
            'document' => '00000000000191',
            'foundation_date' => '2020-01-01',
            'IE' => '123456789',
            'address' => 'Praça da Sé',
            'number' => '100',
            'complement' => 'Sala 2',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01001000',
            'phone' => '11999999999',
            'email' => 'endereco@empresa.com',
            'active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('enterprises', [
            'email' => 'endereco@empresa.com',
            'address' => 'Praça da Sé',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01001000',
        ]);
    }
}
