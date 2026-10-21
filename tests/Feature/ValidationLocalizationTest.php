<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Garante que as mensagens de validação retornadas ao usuário saem em
 * português (locale pt_BR + arquivos em lang/pt_BR), e não no padrão inglês
 * do Laravel. Protege contra regressão caso o locale ou os arquivos de
 * tradução sejam alterados.
 */
class ValidationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function createEnterprise(): int
    {
        return DB::table('enterprises')->insertGetId([
            'name' => 'Teste',
            'fantasy_name' => 'Teste Fantasia',
            'owner_name' => 'Dono Teste',
            'social_reason' => 'Teste LTDA',
            'document' => '00000000000191',
            'foundation_date' => '2000-01-01',
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'city' => 'Cidade Teste',
            'state' => 'SP',
            'zip_code' => '12345678',
            'phone' => '11999999999',
            'email' => 'teste@teste.com',
            'active' => true,
        ]);
    }

    public function test_locale_is_pt_br(): void
    {
        $this->assertSame('pt_BR', config('app.locale'));
    }

    public function test_duplicate_email_message_is_in_portuguese(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = User::factory()->create(['is_admin' => true, 'enterprise_id' => $enterpriseId]);

        // Atleta existente com o e-mail que será duplicado (mesma empresa).
        Athlete::factory()->create([
            'enterprise_id' => $enterpriseId,
            'owner_id'      => $manager->id,
            'document'      => '71517684005',
            'email'         => 'repetido@teste.com',
        ]);

        $payload = [
            'full_name'     => 'Atleta Teste',
            'birth_date'    => '2000-01-01',
            'marital_status' => 'solteiro',
            'gender'        => 'masculino',
            'document'      => '529.982.247-25',
            'address'       => 'Rua Teste',
            'number'        => '123',
            'neighborhood'  => 'Bairro Teste',
            'zip_code'      => '12345-678',
            'state'         => 'SP',
            'city'          => 'Cidade Teste',
            'mobile_phone'  => '(11) 99999-9999',
            'email'         => 'repetido@teste.com', // duplicado
        ];

        $response = $this->actingAs($manager, 'api')
            ->postJson('/api/athletes', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        // A regra email.unique não tem mensagem customizada no Request, então
        // vem da tradução pt_BR — deve estar em português, com o rótulo do campo.
        $this->assertSame(
            'O campo e-mail já está em uso.',
            $response->json('errors.email.0')
        );
    }

    public function test_password_rule_message_is_in_portuguese(): void
    {
        // Super Admin (sem empresa) pode criar usuário e definir a senha.
        $superAdmin = User::factory()->create(['is_admin' => true, 'enterprise_id' => null]);
        $enterpriseId = $this->createEnterprise();

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson('/api/register', [
                'name'          => 'Novo Usuário',
                'email'         => 'novo@teste.com',
                'password'      => 'senhafraca', // sem maiúscula, número e símbolo
                'enterprise_id' => $enterpriseId,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        // As mensagens da regra Password vêm de lang/pt_BR/validation.php (password.*).
        $messages = implode(' ', $response->json('errors.password'));
        $this->assertStringContainsString('senha', $messages);
        $this->assertStringNotContainsString('The ', $messages);
    }
}
