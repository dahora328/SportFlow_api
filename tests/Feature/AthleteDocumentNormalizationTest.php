<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AthleteDocumentNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private function createEnterprise(string $suffix = 'a'): int
    {
        return DB::table('enterprises')->insertGetId([
            'name' => "Teste {$suffix}",
            'fantasy_name' => "Teste Fantasia {$suffix}",
            'owner_name' => "Dono Teste {$suffix}",
            'social_reason' => "Teste LTDA {$suffix}",
            // enterprises.document também é único: varia por sufixo.
            'document' => str_pad((string) crc32($suffix), 14, '0', STR_PAD_LEFT),
            'foundation_date' => '2000-01-01',
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'city' => 'Cidade Teste',
            'state' => 'SP',
            'zip_code' => '12345678',
            'phone' => '11999999999',
            'email' => "teste-{$suffix}@teste.com",
            'active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Atleta Teste',
            'birth_date' => '2000-01-01',
            'marital_status' => 'solteiro',
            'gender' => 'masculino',
            'document' => '715.176.840-05', // CPF válido, mascarado
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'zip_code' => '12345-678',
            'state' => 'SP',
            'city' => 'Cidade Teste',
            'mobile_phone' => '(11) 99999-9999',
            'email' => 'atleta@teste.com',
        ], $overrides);
    }

    public function test_store_saves_document_as_digits(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseId]);

        $this->actingAs($manager, 'api')
            ->postJson('/api/athletes', $this->payload())
            ->assertStatus(201);

        // Gravado só com dígitos (CPF, telefone e CEP).
        $this->assertDatabaseHas('athletes', [
            'document' => '71517684005',
            'mobile_phone' => '11999999999',
            'zip_code' => '12345678',
        ]);
    }

    public function test_duplicate_document_returns_422(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseId]);

        // Já existe com o CPF em dígitos.
        Athlete::factory()->create([
            'enterprise_id' => $enterpriseId,
            'owner_id' => $manager->id,
            'document' => '71517684005',
            'email' => 'ja@existe.com',
        ]);

        // Tenta cadastrar o MESMO CPF, mas mascarado -> deve ser bloqueado (422).
        $this->actingAs($manager, 'api')
            ->postJson('/api/athletes', $this->payload(['email' => 'novo@teste.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');
    }

    public function test_model_mutator_strips_mask(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseId]);
        $this->actingAs($manager, 'api'); // Auth para o booted() preencher owner/enterprise

        $athlete = Athlete::factory()->create(['document' => '529.982.247-25']);

        // O mutator setDocumentAttribute remove a máscara ao salvar.
        $this->assertSame('52998224725', $athlete->fresh()->document);
    }

    public function test_same_document_allowed_in_different_enterprises(): void
    {
        // Sem autenticar, para que o enterprise_id explícito seja respeitado
        // (o booted() só sobrescreve quando há usuário logado).
        $enterpriseA = $this->createEnterprise('a');
        $enterpriseB = $this->createEnterprise('b');
        $ownerA = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseA]);
        $ownerB = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseB]);

        Athlete::factory()->create([
            'enterprise_id' => $enterpriseA,
            'owner_id'      => $ownerA->id,
            'document'      => '52998224725',
        ]);

        // Mesmo CPF, empresa diferente: permitido pelo índice parcial composto.
        Athlete::factory()->create([
            'enterprise_id' => $enterpriseB,
            'owner_id'      => $ownerB->id,
            'document'      => '52998224725',
        ]);

        $this->assertSame(2, Athlete::withoutGlobalScope('enterprise')->where('document', '52998224725')->count());
    }

    public function test_trashed_document_can_be_reregistered(): void
    {
        $enterpriseId = $this->createEnterprise();
        $owner = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseId]);

        $first = Athlete::factory()->create([
            'enterprise_id' => $enterpriseId,
            'owner_id'      => $owner->id,
            'document'      => '52998224725',
        ]);

        // Manda para a lixeira (soft delete) -> libera o CPF.
        $first->delete();

        // Recadastro do mesmo CPF na mesma empresa: não deve violar o índice.
        Athlete::factory()->create([
            'enterprise_id' => $enterpriseId,
            'owner_id'      => $owner->id,
            'document'      => '52998224725',
        ]);

        // 1 ativo + 1 na lixeira = 2 registros no total.
        // withoutGlobalScope('enterprise') remove só o escopo de empresa e
        // mantém o filtro de soft delete, então conta apenas os ativos.
        $this->assertSame(1, Athlete::withoutGlobalScope('enterprise')->where('document', '52998224725')->count());
        $this->assertSame(2, Athlete::withoutGlobalScope('enterprise')->withTrashed()->where('document', '52998224725')->count());
    }

    public function test_store_allows_document_existing_in_another_enterprise(): void
    {
        $enterpriseA = $this->createEnterprise('a');
        $enterpriseB = $this->createEnterprise('b');
        $ownerA = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseA]);
        $managerB = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseB]);

        // CPF já existe na empresa A.
        Athlete::factory()->create([
            'enterprise_id' => $enterpriseA,
            'owner_id'      => $ownerA->id,
            'document'      => '71517684005',
            'email'         => 'a@empresa-a.com',
        ]);

        // Gestor da empresa B cadastra o MESMO CPF -> deve ser permitido (201).
        $this->actingAs($managerB, 'api')
            ->postJson('/api/athletes', $this->payload(['email' => 'b@empresa-b.com']))
            ->assertStatus(201);

        $this->assertSame(2, Athlete::withoutGlobalScope('enterprise')->where('document', '71517684005')->count());
    }
}
